<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use Illuminate\Http\Request;
use App\Models\MultiplayerGame;
use App\Models\ParticipantAnswer;
use App\Models\RoomParticipant;
use App\Models\RoomSettings;
use App\MultiplayerGameStatus;
use App\ParticipantStatus;
use App\Services\MultiplayerGameService;
use App\Services\OpenTriviaService;
use App\Utilities\GameUtilities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class MultiplayerGameController extends Controller
{
    public function __construct(
        private OpenTriviaService $triviaService
    ) {}
    /**
     * Show the active game state with polling data
     *
     * @param string $roomCode
     * @return Response|RedirectResponse
     */
    public function show(string $roomCode): Response|RedirectResponse
    {
        $room = GameRoom::where('room_code', $roomCode)
            ->with(['host', 'participants.user', 'settings', 'multiplayerGame'])
            ->first();

        // If room doesn't exist (cancelled/deleted), redirect to lobby
        if (!$room) {
            return redirect()->route('lobby.index')
                ->with('info', 'This game has been cancelled or no longer exists.');
        }

        // Ensure user is a participant
        $participant = $room->participants()
            ->where('user_id', auth()->id())
            ->first();

        if (!$participant) {
            return redirect()->route('multiplayer.lobby')
                ->withErrors(['room' => 'You are not a participant in this room.']);
        }

        $multiplayerGame = $room->multiplayerGame;

        if (!$multiplayerGame) {
            return redirect()->route('multiplayer.room.show', $roomCode)
                ->withErrors(['game' => 'Game has not started yet.']);
        }

        // If game is completed, redirect to results page
        if ($multiplayerGame->status === MultiplayerGameStatus::COMPLETED) {
            return redirect()->route('multiplayer.game.results', $roomCode);
        }

        // Get current question
        $currentQuestion = $multiplayerGame->currentQuestion();

        if (!$currentQuestion) {
            return redirect()->route('multiplayer.game.results', $roomCode);
        }

        // Calculate time remaining
        $timeRemaining = $this->calculateTimeRemaining($multiplayerGame);

        // Get participant answer status
        $participantStatuses = $this->getParticipantAnswerStatuses($multiplayerGame, $room);

        // Check if current user has answered
        $hasAnswered = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
            ->where('participant_id', $participant->id)
            ->where('question_id', $multiplayerGame->current_question_index)
            ->exists();

        // Initialize game service
        $gameService = app(MultiplayerGameService::class);

        // Calculate new state flags
        $allPlayersAnswered = $gameService->allParticipantsAnswered($multiplayerGame);
        $currentUserHasAnswered = $gameService->currentUserHasAnswered($multiplayerGame, auth()->id());
        $isReadyForNext = $multiplayerGame->isReadyForNext();
        
        // Calculate ready_since timestamp (when the ready state was first reached)
        // This is used for auto-advance countdown on frontend
        $readySince = null;
        if ($isReadyForNext) {
            // If all players answered, use the timestamp of the last answer
            if ($allPlayersAnswered) {
                $lastAnswer = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
                    ->where('question_id', $multiplayerGame->current_question_index)
                    ->orderBy('answered_at', 'desc')
                    ->first();
                $readySince = $lastAnswer?->answered_at?->toIso8601String();
            } else {
                // If time expired, calculate when timer reached 0
                $timePerQuestion = $room->settings->time_per_question ?? RoomSettings::DEFAULT_TIME_PER_QUESTION;
                $readySince = $multiplayerGame->question_started_at
                    ->addSeconds($timePerQuestion)
                    ->toIso8601String();
            }
        }

        return Inertia::render('multiplayer/Game', [
            'gameState' => [
                'room' => [
                    'id' => $room->id,
                    'room_code' => $room->room_code,
                    'status' => $room->status->value,
                    'host_user_id' => $room->host_user_id,
                    'max_players' => $room->max_players,
                    'current_players' => $room->current_players,
                    'settings' => [
                        'time_per_question' => $room->settings->time_per_question,
                        'category_id' => $room->settings->category_id,
                        'difficulty' => $room->settings->difficulty->value,
                        'total_questions' => $room->settings->total_questions,
                    ],
                ],
                'game_status' => $multiplayerGame->status->value,
                'current_question' => $currentQuestion,
                'current_question_index' => $multiplayerGame->current_question_index,
                'time_remaining' => $timeRemaining,
                'participants' => $participantStatuses,
                'round_results' => null, // Will be populated when showing results
                
                // New state flags for answer tracking and progression
                'all_players_answered' => $allPlayersAnswered,
                'current_user_has_answered' => $currentUserHasAnswered,
                'is_ready_for_next' => $isReadyForNext,
                'ready_since' => $readySince,
            ],
        ]);
    }

    /**
     * Submit an answer with timing validation
     *
     * @param string $roomCode
     * @param Request $request
     * @param MultiplayerGameService $gameService
     * @return RedirectResponse
     */
    public function answer(string $roomCode, Request $request, MultiplayerGameService $gameService): RedirectResponse
    {
        $room = GameRoom::where('room_code', $roomCode)
            ->with(['multiplayerGame'])
            ->firstOrFail();

        $participant = $room->participants()
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $multiplayerGame = $room->multiplayerGame;

        if (!$multiplayerGame) {
            return back()->withErrors(['answer' => 'Game has not started yet.']);
        }

        $validated = $request->validate([
            'selected_answer' => ['required', 'string', 'max:500'],
            'question_index' => ['required', 'integer', 'min:0'],
        ]);
        $questionIndex = $validated['question_index'];
        $selectedAnswer = $validated['selected_answer'];

        // Validate question index matches current question
        if ($questionIndex !== $multiplayerGame->current_question_index) {
            return back()->withErrors(['answer' => 'Invalid question index.']);
        }

        // Check if time has expired
        $timeRemaining = $this->calculateTimeRemaining($multiplayerGame);
        if ($timeRemaining <= 0) {
            return back()->withErrors(['answer' => 'Time has expired for this question.']);
        }

        // Check if participant has already answered this question
        $existingAnswer = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
            ->where('participant_id', $participant->id)
            ->where('question_id', $questionIndex)
            ->first();

        if ($existingAnswer) {
            return back()->withErrors(['answer' => 'You have already answered this question.']);
        }

        // Get the question from the game's questions array
        $question = $multiplayerGame->game->questions[$questionIndex];
        $correctAnswer = $question['correct_answer'];
        $isCorrect = GameUtilities::isAnswerCorrect($selectedAnswer, $correctAnswer);

        // Calculate response time
        $responseTimeMs = $this->calculateResponseTime($multiplayerGame);

        // Create participant answer
        ParticipantAnswer::create([
            'multiplayer_game_id' => $multiplayerGame->id,
            'participant_id' => $participant->id,
            'question_id' => $questionIndex,
            'selected_answer' => $selectedAnswer,
            'is_correct' => $isCorrect,
            'answered_at' => now(),
            'response_time_ms' => $responseTimeMs,
        ]);

        // Return back to the game page (polling will update the state)
        return back();
    }

    /**
     * Advance to next question (host-triggered)
     *
     * @param string $roomCode
     * @param MultiplayerGameService $gameService
     * @return RedirectResponse
     */
    public function nextQuestion(string $roomCode, MultiplayerGameService $gameService): RedirectResponse
    {
        $room = GameRoom::where('room_code', $roomCode)
            ->with(['multiplayerGame'])
            ->firstOrFail();

        $multiplayerGame = $room->multiplayerGame;

        if (!$multiplayerGame) {
            return back()->withErrors(['error' => 'Game has not started yet.']);
        }

        // Verify user is host
        if ($room->host_user_id !== auth()->id()) {
            return back()->withErrors(['error' => 'Only the host can advance questions.']);
        }

        // Verify game is active
        if ($multiplayerGame->status !== MultiplayerGameStatus::ACTIVE) {
            return back()->withErrors(['error' => 'Game is not active.']);
        }

        // Verify ready state (timer expired OR all answered)
        if (!$multiplayerGame->isReadyForNext()) {
            return back()->withErrors(['error' => 'Not ready to advance yet. Wait for timer to expire or all players to answer.']);
        }

        // Use database transaction for score calculation and state updates
        DB::transaction(function () use ($gameService, $multiplayerGame) {
            $gameService->advanceToNextQuestion($multiplayerGame);
        });

        // Refresh the model to get updated state
        $multiplayerGame->refresh();

        // If game is completed, redirect to results
        if ($multiplayerGame->status === MultiplayerGameStatus::COMPLETED) {
            return redirect()->route('multiplayer.game.results', $roomCode);
        }

        // Otherwise, stay on game page (polling will show new question)
        return redirect()->route('multiplayer.game.show', $roomCode);
    }

    /**
     * Show round results or final results
     *
     * @param string $roomCode
     * @return Response|RedirectResponse
     */
    public function results(string $roomCode): Response|RedirectResponse
    {
        $room = GameRoom::where('room_code', $roomCode)
            ->with(['host', 'participants.user', 'settings', 'multiplayerGame'])
            ->firstOrFail();

        // Ensure user is a participant
        $participant = $room->participants()
            ->where('user_id', auth()->id())
            ->first();

        if (!$participant) {
            return redirect()->route('multiplayer.lobby')
                ->withErrors(['room' => 'You are not a participant in this room.']);
        }

        $multiplayerGame = $room->multiplayerGame;

        if (!$multiplayerGame) {
            return redirect()->route('multiplayer.room.show', $roomCode)
                ->withErrors(['game' => 'Game has not started yet.']);
        }

        // Determine if showing round results or final results
        $isFinalResults = $multiplayerGame->status === MultiplayerGameStatus::COMPLETED;

        if ($isFinalResults) {
            return $this->showFinalResults($room, $multiplayerGame);
        } else {
            return $this->showRoundResults($room, $multiplayerGame);
        }
    }

    /**
     * Show round results after a question
     *
     * @param GameRoom $room
     * @param MultiplayerGame $multiplayerGame
     * @return Response
     */
    private function showRoundResults(GameRoom $room, MultiplayerGame $multiplayerGame): Response
    {
        $currentQuestionIndex = $multiplayerGame->current_question_index;
        $question = $multiplayerGame->game->questions[$currentQuestionIndex];

        // Get participant results for this question
        $participantResults = $this->getParticipantResultsForQuestion(
            $multiplayerGame,
            $room,
            $currentQuestionIndex
        );

        // Get current leaderboard
        $leaderboard = $this->generateLeaderboard($room);

        return Inertia::render('multiplayer/Game', [
            'gameState' => [
                'room' => [
                    'id' => $room->id,
                    'room_code' => $room->room_code,
                    'status' => $room->status->value,
                    'host_user_id' => $room->host_user_id,
                    'max_players' => $room->max_players,
                    'current_players' => $room->current_players,
                    'settings' => [
                        'time_per_question' => $room->settings->time_per_question,
                        'category_id' => $room->settings->category_id,
                        'difficulty' => $room->settings->difficulty->value,
                        'total_questions' => $room->settings->total_questions,
                    ],
                ],
                'game_status' => $multiplayerGame->status->value,
                'current_question' => null,
                'current_question_index' => $currentQuestionIndex,
                'time_remaining' => 0,
                'participants' => [],
                'round_results' => [
                    'leaderboard' => $leaderboard,
                    'question' => [
                        'question' => $question['question'],
                        'correct_answer' => $question['correct_answer'],
                        'all_answers' => $question['shuffled_answers'] ?? [],
                    ],
                    'correct_answer' => $question['correct_answer'],
                    'participant_results' => $participantResults,
                    'has_more_questions' => ($currentQuestionIndex + 1) < $multiplayerGame->game->total_questions,
                ],
            ],
        ]);
    }

    /**
     * Get all questions with current user's answers for review
     *
     * @param MultiplayerGame $multiplayerGame
     * @param int $userId
     * @return array
     */
    private function getQuestionsWithUserAnswers(MultiplayerGame $multiplayerGame, int $userId): array
    {
        // Get participant for current user
        $participant = $multiplayerGame->room->participants()
            ->where('user_id', $userId)
            ->first();

        if (!$participant) {
            return [];
        }

        // Get all questions from the game
        $questions = $multiplayerGame->game->questions ?? [];

        // Get all answers for this participant
        $answers = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
            ->where('participant_id', $participant->id)
            ->get()
            ->keyBy('question_id');

        // Map questions with user answers
        return collect($questions)->map(function ($question, $index) use ($answers) {
            $answer = $answers->get($index);

            return [
                'question_number' => $index + 1,
                'question_text' => $question['question'],
                'correct_answer' => $question['correct_answer'],
                'all_answers' => $question['shuffled_answers'] ?? [],
                'user_answer' => $answer?->selected_answer,
                'is_correct' => $answer?->is_correct ?? false,
                'answered' => $answer !== null,
                'points_earned' => $answer?->calculateScore() ?? 0,
            ];
        })->toArray();
    }

    /**
     * Show final results after game completion
     *
     * @param GameRoom $room
     * @param MultiplayerGame $multiplayerGame
     * @return Response
     */
    private function showFinalResults(GameRoom $room, MultiplayerGame $multiplayerGame): Response
    {
        // Get final leaderboard
        $leaderboard = $this->generateLeaderboard($room);

        // Get questions with current user's answers for review
        $questionsReview = $this->getQuestionsWithUserAnswers($multiplayerGame, auth()->id());

        // Get category name if category_id exists
        $categoryName = $this->triviaService->getCategoryName($room->settings->category_id);

        return Inertia::render('multiplayer/Game', [
            'gameState' => [
                'room' => [
                    'id' => $room->id,
                    'room_code' => $room->room_code,
                    'status' => $room->status->value,
                    'host_user_id' => $room->host_user_id,
                    'max_players' => $room->max_players,
                    'current_players' => $room->current_players,
                    'settings' => [
                        'time_per_question' => $room->settings->time_per_question,
                        'category_id' => $room->settings->category_id,
                        'category' => $categoryName,
                        'difficulty' => $room->settings->difficulty->value,
                        'total_questions' => $room->settings->total_questions,
                    ],
                ],
                'game_status' => $multiplayerGame->status->value,
                'current_question' => null,
                'current_question_index' => $multiplayerGame->current_question_index,
                'time_remaining' => 0,
                'participants' => [],
                'round_results' => [
                    'leaderboard' => $leaderboard,
                    'question' => null,
                    'participant_results' => [],
                    'questions_review' => $questionsReview,
                ],
            ],
        ]);
    }

    /**
     * Calculate time remaining for current question
     *
     * @param MultiplayerGame $multiplayerGame
     * @return int
     */
    private function calculateTimeRemaining(MultiplayerGame $multiplayerGame): int
    {
        if (!$multiplayerGame->question_started_at) {
            return 0;
        }

        $timePerQuestion = $multiplayerGame->room->settings->time_per_question ?? RoomSettings::DEFAULT_TIME_PER_QUESTION;
        $elapsedSeconds = $multiplayerGame->question_started_at->diffInSeconds(now());
        $remaining = max(0, $timePerQuestion - $elapsedSeconds);

        return (int) $remaining;
    }

    /**
     * Calculate response time in milliseconds
     *
     * @param MultiplayerGame $multiplayerGame
     * @return int
     */
    private function calculateResponseTime(MultiplayerGame $multiplayerGame): int
    {
        if (!$multiplayerGame->question_started_at) {
            return 0;
        }

        return (int) ($multiplayerGame->question_started_at->diffInMilliseconds(now()));
    }

    /**
     * Get participant answer statuses for current question
     *
     * @param MultiplayerGame $multiplayerGame
     * @param GameRoom $room
     * @return array
     */
    private function getParticipantAnswerStatuses(MultiplayerGame $multiplayerGame, GameRoom $room): array
    {
        $participants = $room->participants()->with('user')->get();
        $currentQuestionIndex = $multiplayerGame->current_question_index;

        return $participants->map(function ($participant) use ($multiplayerGame, $currentQuestionIndex) {
            $hasAnswered = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
                ->where('participant_id', $participant->id)
                ->where('question_id', $currentQuestionIndex)
                ->exists();

            return [
                'id' => $participant->id,
                'user' => [
                    'id' => $participant->user->id,
                    'name' => $participant->user->name,
                ],
                'score' => $participant->score,
                'status' => $participant->status->value,
                'has_answered' => $hasAnswered,
            ];
        })->toArray();
    }

    /**
     * Get participant results for a specific question
     *
     * @param MultiplayerGame $multiplayerGame
     * @param GameRoom $room
     * @param int $questionIndex
     * @return array
     */
    private function getParticipantResultsForQuestion(
        MultiplayerGame $multiplayerGame,
        GameRoom $room,
        int $questionIndex
    ): array {
        $participants = $room->participants()->with('user')->get();

        return $participants->map(function ($participant) use ($multiplayerGame, $questionIndex, $room) {
            $answer = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
                ->where('participant_id', $participant->id)
                ->where('question_id', $questionIndex)
                ->first();

            // Calculate points earned for this question
            $difficulty = $room->settings->difficulty->value;
            $pointsForCorrect = match($difficulty) {
                'easy' => 10,
                'medium' => 20,
                'hard' => 30,
                default => 10,
            };
            $pointsEarned = ($answer?->is_correct ?? false) ? $pointsForCorrect : 0;

            return [
                'participant' => [
                    'id' => $participant->id,
                    'user' => [
                        'id' => $participant->user->id,
                        'name' => $participant->user->name,
                    ],
                    'status' => $participant->status->value,
                ],
                'is_correct' => $answer?->is_correct ?? false,
                'selected_answer' => $answer?->selected_answer ?? null,
                'response_time_ms' => $answer?->response_time_ms ?? null,
                'points_earned' => $pointsEarned,
            ];
        })->toArray();
    }

    /**
     * Generate leaderboard with participant rankings
     *
     * @param GameRoom $room
     * @return array
     */
    private function generateLeaderboard(GameRoom $room): array
    {
        $participants = $room->participants()
            ->with('user')
            ->orderBy('score', 'desc')
            ->get();

        $position = 1;
        return $participants->map(function ($participant) use (&$position) {
            return [
                'position' => $position++,
                'participant' => [
                    'id' => $participant->id,
                    'user' => [
                        'id' => $participant->user->id,
                        'name' => $participant->user->name,
                    ],
                    'status' => $participant->status->value,
                ],
                'score' => $participant->score,
            ];
        })->toArray();
    }
}
