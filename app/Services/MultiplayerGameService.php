<?php

namespace App\Services;

use App\DifficultyLevel;
use App\GameStatus;
use App\Models\Game;
use App\Models\GameRoom;
use App\Models\MultiplayerGame;
use App\Models\ParticipantAnswer;
use App\Models\RoomParticipant;
use App\MultiplayerGameStatus;
use App\ParticipantStatus;
use App\RoomStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MultiplayerGameService
{
    public function __construct(
        private OpenTriviaService $triviaService
    ) {}

    /**
     * Start a multiplayer game for a room
     *
     * @param GameRoom $room
     * @return MultiplayerGame
     * @throws \Exception
     */
    public function startGame(GameRoom $room): MultiplayerGame
    {
        // Verify room is in waiting status
        if ($room->status !== RoomStatus::WAITING) {
            throw new \Exception('Room is not in waiting status');
        }

        // Verify there are participants
        if ($room->participants()->count() === 0) {
            throw new \Exception('No participants in room');
        }

        return DB::transaction(function () use ($room) {
            // Fetch questions from OpenTriviaService
            $questionParams = [
                'amount' => $room->settings->total_questions,
                'difficulty' => $room->settings->difficulty->value,
            ];

            if ($room->settings->category_id) {
                $questionParams['category'] = $room->settings->category_id;
            }

            $questionsResponse = $this->triviaService->getQuestions($questionParams);

            if (isset($questionsResponse['error']) && $questionsResponse['error']) {
                throw new \Exception($questionsResponse['message'] ?? 'Failed to fetch questions');
            }

            if (empty($questionsResponse)) {
                throw new \Exception('No questions available for the selected parameters');
            }

            // Create the base game record
            $game = Game::create([
                'user_id' => $room->host_user_id,
                'category_id' => $room->settings->category_id,
                'difficulty' => $room->settings->difficulty,
                'total_questions' => count($questionsResponse),
                'current_question_index' => 0,
                'score' => 0,
                'status' => GameStatus::Active,
                'questions' => $questionsResponse,
                'started_at' => now(),
            ]);

            // Create multiplayer game record
            $multiplayerGame = MultiplayerGame::create([
                'room_id' => $room->id,
                'game_id' => $game->id,
                'current_question_index' => 0,
                'status' => MultiplayerGameStatus::WAITING,
            ]);

            // Update room status to active
            $room->update([
                'status' => RoomStatus::ACTIVE,
            ]);

            // Update all participants to playing status
            $room->participants()->update([
                'status' => ParticipantStatus::PLAYING,
            ]);

            Log::info('MultiplayerGameService: Game created', [
                'room_id' => $room->id,
                'room_code' => $room->room_code,
                'game_id' => $game->id,
                'multiplayer_game_id' => $multiplayerGame->id,
                'total_questions' => count($questionsResponse)
            ]);

            // Start the game immediately (no countdown for now)
            $multiplayerGame->update([
                'status' => MultiplayerGameStatus::ACTIVE,
                'current_question_index' => 0,
                'question_started_at' => now(),
            ]);

            // Update room status to active
            $room->update([
                'status' => RoomStatus::ACTIVE,
            ]);

            Log::info('MultiplayerGameService: Game started immediately', [
                'multiplayer_game_id' => $multiplayerGame->id,
                'room_code' => $room->room_code
            ]);

            return $multiplayerGame;
        });
    }

    /**
     * Validate answer submission
     *
     * @param MultiplayerGame $multiplayerGame
     * @param RoomParticipant $participant
     * @param int $questionIndex
     * @param string $selectedAnswer
     * @return array
     */
    public function validateAnswer(
        MultiplayerGame $multiplayerGame,
        RoomParticipant $participant,
        int $questionIndex,
        string $selectedAnswer
    ): array {
        // Validate question index
        if ($questionIndex !== $multiplayerGame->current_question_index) {
            return [
                'valid' => false,
                'error' => 'Invalid question index'
            ];
        }

        // Validate game is active
        if ($multiplayerGame->status !== MultiplayerGameStatus::ACTIVE) {
            return [
                'valid' => false,
                'error' => 'Game is not active'
            ];
        }

        // Check if time has expired
        if (!$this->isWithinTimeLimit($multiplayerGame)) {
            return [
                'valid' => false,
                'error' => 'Time has expired for this question'
            ];
        }

        // Check if participant has already answered
        $existingAnswer = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
            ->where('participant_id', $participant->id)
            ->where('question_id', $questionIndex)
            ->exists();

        if ($existingAnswer) {
            return [
                'valid' => false,
                'error' => 'You have already answered this question'
            ];
        }

        return [
            'valid' => true
        ];
    }

    /**
     * Check if answer submission is within time limit
     *
     * @param MultiplayerGame $multiplayerGame
     * @return bool
     */
    public function isWithinTimeLimit(MultiplayerGame $multiplayerGame): bool
    {
        if (!$multiplayerGame->question_started_at) {
            return false;
        }

        $timePerQuestion = $multiplayerGame->room->settings->time_per_question ?? RoomSettings::DEFAULT_TIME_PER_QUESTION;
        $elapsedSeconds = $multiplayerGame->question_started_at->diffInSeconds(now());

        return $elapsedSeconds <= $timePerQuestion;
    }

    /**
     * Calculate time remaining for current question
     *
     * @param MultiplayerGame $multiplayerGame
     * @return int
     */
    public function calculateTimeRemaining(MultiplayerGame $multiplayerGame): int
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
     * Generate leaderboard for a room
     *
     * @param GameRoom $room
     * @return array
     */
    public function generateLeaderboard(GameRoom $room): array
    {
        $participants = $room->participants()
            ->with('user')
            ->orderBy('score', 'desc')
            ->orderBy('joined_at', 'asc') // Tie-breaker: earlier join time
            ->get();

        $position = 1;
        $previousScore = null;
        $actualPosition = 1;

        return $participants->map(function ($participant) use (&$position, &$previousScore, &$actualPosition) {
            // Handle ties - participants with same score get same position
            if ($previousScore !== null && $participant->score !== $previousScore) {
                $position = $actualPosition;
            }

            $result = [
                'position' => $position,
                'participant_id' => $participant->id,
                'user' => [
                    'id' => $participant->user->id,
                    'name' => $participant->user->name,
                ],
                'score' => $participant->score,
                'status' => $participant->status->value,
            ];

            $previousScore = $participant->score;
            $actualPosition++;

            return $result;
        })->toArray();
    }

    /**
     * Get participant statistics for a game
     *
     * @param MultiplayerGame $multiplayerGame
     * @param RoomParticipant $participant
     * @return array
     */
    public function getParticipantStatistics(
        MultiplayerGame $multiplayerGame,
        RoomParticipant $participant
    ): array {
        $answers = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
            ->where('participant_id', $participant->id)
            ->get();

        $correctAnswers = $answers->where('is_correct', true)->count();
        $totalAnswers = $answers->count();
        $averageResponseTime = $answers->avg('response_time_ms');

        return [
            'total_answers' => $totalAnswers,
            'correct_answers' => $correctAnswers,
            'incorrect_answers' => $totalAnswers - $correctAnswers,
            'accuracy_percentage' => $totalAnswers > 0 ? round(($correctAnswers / $totalAnswers) * 100, 1) : 0,
            'average_response_time_ms' => $averageResponseTime ? round($averageResponseTime) : null,
            'total_score' => $participant->score,
        ];
    }

    /**
     * Get round results for a specific question
     *
     * @param MultiplayerGame $multiplayerGame
     * @param int $questionIndex
     * @return array
     */
    public function getRoundResults(MultiplayerGame $multiplayerGame, int $questionIndex): array
    {
        $question = $multiplayerGame->game->questions[$questionIndex];
        $room = $multiplayerGame->room;

        // Get all participant answers for this question
        $answers = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
            ->where('question_id', $questionIndex)
            ->with('participant.user')
            ->get();

        $participantResults = $room->participants()->with('user')->get()->map(function ($participant) use ($answers) {
            $answer = $answers->firstWhere('participant_id', $participant->id);

            return [
                'participant_id' => $participant->id,
                'user' => [
                    'id' => $participant->user->id,
                    'name' => $participant->user->name,
                ],
                'score' => $participant->score,
                'is_correct' => $answer?->is_correct ?? false,
                'selected_answer' => $answer?->selected_answer ?? null,
                'response_time_ms' => $answer?->response_time_ms ?? null,
                'answered' => $answer !== null,
            ];
        });

        return [
            'question' => [
                'question' => $question['question'],
                'correct_answer' => $question['correct_answer'],
                'all_answers' => $question['shuffled_answers'] ?? [],
            ],
            'participant_results' => $participantResults->toArray(),
            'leaderboard' => $this->generateLeaderboard($room),
        ];
    }

    /**
     * Check if all active participants have answered the current question
     *
     * @param MultiplayerGame $multiplayerGame
     * @return bool
     */
    public function allParticipantsAnswered(MultiplayerGame $multiplayerGame): bool
    {
        $room = $multiplayerGame->room;
        $currentQuestionIndex = $multiplayerGame->current_question_index;

        // Count active participants with PLAYING status
        $activeParticipantCount = $room->participants()
            ->where('status', ParticipantStatus::PLAYING)
            ->count();

        // Handle edge case of zero active participants
        if ($activeParticipantCount === 0) {
            return false;
        }

        // Count ParticipantAnswer records for current question
        $answerCount = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
            ->where('question_id', $currentQuestionIndex)
            ->count();

        // Return true only if answer count equals or exceeds active participant count
        return $answerCount >= $activeParticipantCount;
    }

    /**
     * Check if current user has answered the current question
     *
     * @param MultiplayerGame $multiplayerGame
     * @param int $userId
     * @return bool
     */
    public function currentUserHasAnswered(MultiplayerGame $multiplayerGame, int $userId): bool
    {
        $participant = $multiplayerGame->room->participants()
            ->where('user_id', $userId)
            ->first();

        if (!$participant) {
            return false;
        }

        return ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
            ->where('participant_id', $participant->id)
            ->where('question_id', $multiplayerGame->current_question_index)
            ->exists();
    }

    /**
     * Calculate scores for current question (synchronous)
     *
     * @param MultiplayerGame $multiplayerGame
     * @return void
     */
    public function calculateRoundScores(MultiplayerGame $multiplayerGame): void
    {
        $currentQuestionIndex = $multiplayerGame->current_question_index;
        $difficulty = $multiplayerGame->room->settings->difficulty;

        $pointsForCorrect = match($difficulty) {
            DifficultyLevel::Easy => 10,
            DifficultyLevel::Medium => 20,
            DifficultyLevel::Hard => 30,
        };

        $answers = ParticipantAnswer::where('multiplayer_game_id', $multiplayerGame->id)
            ->where('question_id', $currentQuestionIndex)
            ->with('participant')
            ->get();

        foreach ($answers as $answer) {
            if ($answer->is_correct) {
                $answer->participant->addScore($pointsForCorrect);
            }
        }

        Log::info('MultiplayerGameService: Round scores calculated', [
            'multiplayer_game_id' => $multiplayerGame->id,
            'question_index' => $currentQuestionIndex,
            'points_awarded' => $pointsForCorrect,
            'answers_processed' => $answers->count()
        ]);
    }

    /**
     * Advance to next question or complete game (synchronous)
     *
     * @param MultiplayerGame $multiplayerGame
     * @return void
     */
    public function advanceToNextQuestion(MultiplayerGame $multiplayerGame): void
    {
        // Calculate scores for current question
        $this->calculateRoundScores($multiplayerGame);

        // Check if there are more questions
        $nextIndex = $multiplayerGame->current_question_index + 1;
        $totalQuestions = $multiplayerGame->game->total_questions;

        if ($nextIndex >= $totalQuestions) {
            // Complete the game
            $multiplayerGame->update([
                'status' => MultiplayerGameStatus::COMPLETED,
            ]);

            $multiplayerGame->room->update([
                'status' => RoomStatus::COMPLETED,
            ]);

            Log::info('MultiplayerGameService: Game completed', [
                'multiplayer_game_id' => $multiplayerGame->id,
                'room_code' => $multiplayerGame->room->room_code,
                'final_question_index' => $multiplayerGame->current_question_index
            ]);

            return;
        }

        // Move to next question
        $multiplayerGame->update([
            'current_question_index' => $nextIndex,
            'question_started_at' => now(),
        ]);

        Log::info('MultiplayerGameService: Advanced to next question', [
            'multiplayer_game_id' => $multiplayerGame->id,
            'room_code' => $multiplayerGame->room->room_code,
            'new_question_index' => $nextIndex
        ]);
    }

    /**
     * Cancel a multiplayer game
     *
     * @param MultiplayerGame $multiplayerGame
     * @return void
     */
    public function cancelGame(MultiplayerGame $multiplayerGame): void
    {
        DB::transaction(function () use ($multiplayerGame) {
            $multiplayerGame->update([
                'status' => MultiplayerGameStatus::COMPLETED,
            ]);

            $multiplayerGame->room->update([
                'status' => RoomStatus::CANCELLED,
            ]);

            $multiplayerGame->room->participants()->update([
                'status' => ParticipantStatus::FINISHED,
            ]);

            Log::info('MultiplayerGameService: Game cancelled', [
                'multiplayer_game_id' => $multiplayerGame->id,
                'room_code' => $multiplayerGame->room->room_code
            ]);
        });
    }
}
