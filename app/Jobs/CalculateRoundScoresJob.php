<?php

namespace App\Jobs;

use App\DifficultyLevel;
use App\Models\MultiplayerGame;
use App\Models\ParticipantAnswer;
use App\Models\RoomParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateRoundScoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $multiplayerGameId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $multiplayerGame = MultiplayerGame::with(['game', 'room'])->find($this->multiplayerGameId);

        if (!$multiplayerGame) {
            Log::warning('CalculateRoundScoresJob: MultiplayerGame not found', [
                'multiplayer_game_id' => $this->multiplayerGameId
            ]);
            return;
        }

        $currentQuestionIndex = $multiplayerGame->current_question_index;

        // Get all answers for the current question
        $answers = ParticipantAnswer::where('multiplayer_game_id', $this->multiplayerGameId)
            ->where('question_index', $currentQuestionIndex)
            ->get();

        Log::info('CalculateRoundScoresJob: Calculating scores', [
            'multiplayer_game_id' => $this->multiplayerGameId,
            'question_index' => $currentQuestionIndex,
            'answer_count' => $answers->count()
        ]);

        // Calculate points for each answer
        DB::transaction(function () use ($answers, $multiplayerGame) {
            foreach ($answers as $answer) {
                if ($answer->is_correct) {
                    $points = $this->calculatePoints(
                        $multiplayerGame->game->difficulty,
                        $answer->response_time_ms
                    );

                    // Update participant score
                    $participant = RoomParticipant::find($answer->participant_id);
                    if ($participant) {
                        $participant->increment('score', $points);

                        Log::debug('CalculateRoundScoresJob: Updated participant score', [
                            'participant_id' => $participant->id,
                            'points_added' => $points,
                            'new_score' => $participant->score
                        ]);
                    }
                }
            }
        });

        Log::info('CalculateRoundScoresJob: Scores calculated', [
            'multiplayer_game_id' => $this->multiplayerGameId,
            'question_index' => $currentQuestionIndex
        ]);
    }

    /**
     * Calculate points based on difficulty and response time
     *
     * @param DifficultyLevel $difficulty
     * @param int $responseTimeMs
     * @return int
     */
    private function calculatePoints(DifficultyLevel $difficulty, int $responseTimeMs): int
    {
        // Base points by difficulty
        $basePoints = match ($difficulty) {
            DifficultyLevel::Easy => 10,
            DifficultyLevel::Medium => 20,
            DifficultyLevel::Hard => 30,
        };

        // Standard scoring mode: just return base points
        // Future: Could add time-based bonuses for faster answers
        return $basePoints;
    }
}
