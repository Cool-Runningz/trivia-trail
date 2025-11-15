<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use Illuminate\Http\Request;
use App\Models\MultiplayerGame;
use App\Models\ParticipantAnswer;
use App\Models\RoomParticipant;
use App\MultiplayerGameStatus;
use App\ParticipantStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MultiplayerGameController extends Controller
{
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
            ->where('question_index', $multiplayerGame->current_question_index)
            ->exists();

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
                'current_question' => $currentQuestion,
                'current_question_index' => $multiplayerGame->current_question_index,
                'time_remaining' => $timeRemaining,
                'participants' => $participantStatuses,
                'round_results' => null, // Will be populated when showing results
            ],
        ]);
    }

    /**
     * Submit an answer with timing validation
     *
     * @param string $roomCode
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function answer(string $roomCode, Request $request): JsonResponse|RedirectResponse
    {
        $room = GameRoom::where('room_code', $roomCode)
            ->with(['multiplayerGame'])
            ->firstOrFail();

        $participant = $room->participants()
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $multiplayerGame = $room->multiplayerGame;

        if (!$multiplayerGame) {
            return response()->json([
                'error' => 'Game has not started yet.'
            ], 400);
        }

        $validated = $request->validate([
            'selected_answer' => ['required', 'string', 'max:500'],
            'question_index' => ['required', 'integer', 'min:0'],
        ]);
        $questionIndex = $validated['question_index'];
        $selectedAnswer = $validated['selected_answer'];

        // Validate question index matches current question
        if ($questionIndex !== $multiplayerGame->current_question_index) {
            return response()->json([
                'error' => 'Invalid question index.'
            ], 400);
        }

        // Check if time has expired
        $timeRemaining = $this->calculateTimeRemaining($multiplayerGame);
        if ($timeRemaining <= 0) {
            return response()->json([
                'error' => 'Time has expired for this question.'
            ], 400);
        }

        // Check if participant has already answered this question
        $existingAnswer = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
            ->where('participant_id', $participant->id)
            ->where('question_index', $questionIndex)
            ->first();

        if ($existingAnswer) {
            return response()->json([
                'error' => 'You have already answered this question.'
            ], 400);
        }

        // Get the question from the game's questions array
        $question = $multiplayerGame->game->questions[$questionIndex];
        $correctAnswer = $question['correct_answer'];
        $isCorrect = $selectedAnswer === $correctAnswer;

        // Calculate response time
        $responseTimeMs = $this->calculateResponseTime($multiplayerGame);

        // Create participant answer
        ParticipantAnswer::create([
            'multiplayer_game_id' => $multiplayerGame->id,
            'participant_id' => $participant->id,
            'question_index' => $questionIndex,
            'question' => $question['question'],
            'selected_answer' => $selectedAnswer,
            'correct_answer' => $correctAnswer,
            'is_correct' => $isCorrect,
            'answered_at' => now(),
            'response_time_ms' => $responseTimeMs,
        ]);

        return response()->json([
            'success' => true,
            'is_correct' => $isCorrect,
        ]);
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
        $isFinalResults = $multiplayerGame->status === MultiplayerGameStatus::Completed;

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

        return Inertia::render('multiplayer/game/round-results', [
            'room' => [
                'id' => $room->id,
                'room_code' => $room->room_code,
                'status' => $room->status->value,
            ],
            'game' => [
                'id' => $multiplayerGame->id,
                'current_question_index' => $currentQuestionIndex,
                'total_questions' => $multiplayerGame->game->total_questions,
                'status' => $multiplayerGame->status->value,
            ],
            'question' => [
                'question' => $question['question'],
                'correct_answer' => $question['correct_answer'],
                'all_answers' => $question['shuffled_answers'] ?? [],
            ],
            'participantResults' => $participantResults,
            'leaderboard' => $leaderboard,
            'hasMoreQuestions' => ($currentQuestionIndex + 1) < $multiplayerGame->game->total_questions,
        ]);
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

        // Get all participant answers for detailed breakdown
        $participantAnswers = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
            ->with('participant.user')
            ->get()
            ->groupBy('participant_id');

        return Inertia::render('multiplayer/game/final-results', [
            'room' => [
                'id' => $room->id,
                'room_code' => $room->room_code,
                'status' => $room->status->value,
            ],
            'game' => [
                'id' => $multiplayerGame->id,
                'total_questions' => $multiplayerGame->game->total_questions,
                'status' => $multiplayerGame->status->value,
            ],
            'leaderboard' => $leaderboard,
            'totalQuestions' => $multiplayerGame->game->total_questions,
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

        $timePerQuestion = $multiplayerGame->game->gameRoom->settings->time_per_question ?? 30;
        $elapsedSeconds = now()->diffInSeconds($multiplayerGame->question_started_at);
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

        return (int) (now()->diffInMilliseconds($multiplayerGame->question_started_at));
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
                ->where('question_index', $currentQuestionIndex)
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

        return $participants->map(function ($participant) use ($multiplayerGame, $questionIndex) {
            $answer = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
                ->where('participant_id', $participant->id)
                ->where('question_index', $questionIndex)
                ->first();

            return [
                'id' => $participant->id,
                'user' => [
                    'id' => $participant->user->id,
                    'name' => $participant->user->name,
                ],
                'score' => $participant->score,
                'is_correct' => $answer?->is_correct ?? false,
                'selected_answer' => $answer?->selected_answer ?? null,
                'response_time_ms' => $answer?->response_time_ms ?? null,
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
                'user' => [
                    'id' => $participant->user->id,
                    'name' => $participant->user->name,
                ],
                'score' => $participant->score,
                'status' => $participant->status->value,
            ];
        })->toArray();
    }
}
