<?php

namespace App\Jobs;

use App\Models\MultiplayerGame;
use App\MultiplayerGameStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NextQuestionJob implements ShouldQueue
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
        $multiplayerGame = MultiplayerGame::with(['game', 'room.settings'])->find($this->multiplayerGameId);

        if (!$multiplayerGame) {
            Log::warning('NextQuestionJob: MultiplayerGame not found', [
                'multiplayer_game_id' => $this->multiplayerGameId
            ]);
            return;
        }

        // Verify game is still active
        if ($multiplayerGame->status !== MultiplayerGameStatus::Active) {
            Log::info('NextQuestionJob: Game is not active', [
                'multiplayer_game_id' => $this->multiplayerGameId,
                'status' => $multiplayerGame->status->value
            ]);
            return;
        }

        // Calculate scores for the current question
        CalculateRoundScoresJob::dispatchSync($this->multiplayerGameId);

        // Show results briefly
        $multiplayerGame->update([
            'status' => MultiplayerGameStatus::ShowingResults,
        ]);

        Log::info('NextQuestionJob: Showing results', [
            'multiplayer_game_id' => $this->multiplayerGameId,
            'current_question_index' => $multiplayerGame->current_question_index
        ]);

        // Check if there are more questions
        $nextQuestionIndex = $multiplayerGame->current_question_index + 1;
        $totalQuestions = $multiplayerGame->game->total_questions;

        if ($nextQuestionIndex >= $totalQuestions) {
            // Game is complete
            $this->completeGame($multiplayerGame);
        } else {
            // Move to next question after showing results
            $this->startNextQuestion($multiplayerGame, $nextQuestionIndex);
        }
    }

    /**
     * Complete the game
     *
     * @param MultiplayerGame $multiplayerGame
     * @return void
     */
    private function completeGame(MultiplayerGame $multiplayerGame): void
    {
        $multiplayerGame->update([
            'status' => MultiplayerGameStatus::Completed,
        ]);

        $multiplayerGame->room->update([
            'status' => \App\RoomStatus::Completed,
        ]);

        Log::info('NextQuestionJob: Game completed', [
            'multiplayer_game_id' => $this->multiplayerGameId,
            'room_code' => $multiplayerGame->room->room_code
        ]);

        // Schedule cleanup job
        CleanupInactiveRoomsJob::dispatch()
            ->delay(now()->addHours(1));
    }

    /**
     * Start the next question
     *
     * @param MultiplayerGame $multiplayerGame
     * @param int $nextQuestionIndex
     * @return void
     */
    private function startNextQuestion(MultiplayerGame $multiplayerGame, int $nextQuestionIndex): void
    {
        // Wait a few seconds to show results, then start next question
        sleep(5);

        $multiplayerGame->update([
            'status' => MultiplayerGameStatus::Active,
            'current_question_index' => $nextQuestionIndex,
            'question_started_at' => now(),
        ]);

        Log::info('NextQuestionJob: Started next question', [
            'multiplayer_game_id' => $this->multiplayerGameId,
            'question_index' => $nextQuestionIndex
        ]);

        // Schedule the next question progression
        $timePerQuestion = $multiplayerGame->room->settings->time_per_question ?? 30;
        NextQuestionJob::dispatch($this->multiplayerGameId)
            ->delay(now()->addSeconds($timePerQuestion));
    }
}
