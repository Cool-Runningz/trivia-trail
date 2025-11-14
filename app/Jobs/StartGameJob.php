<?php

namespace App\Jobs;

use App\Models\GameRoom;
use App\Models\MultiplayerGame;
use App\MultiplayerGameStatus;
use App\RoomStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StartGameJob implements ShouldQueue
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
            Log::warning('StartGameJob: MultiplayerGame not found', [
                'multiplayer_game_id' => $this->multiplayerGameId
            ]);
            return;
        }

        // Verify game is still in waiting status
        if ($multiplayerGame->status !== MultiplayerGameStatus::Waiting) {
            Log::info('StartGameJob: Game already started or completed', [
                'multiplayer_game_id' => $this->multiplayerGameId,
                'status' => $multiplayerGame->status->value
            ]);
            return;
        }

        // Start the first question
        $multiplayerGame->update([
            'status' => MultiplayerGameStatus::Active,
            'current_question_index' => 0,
            'question_started_at' => now(),
        ]);

        // Update room status
        $multiplayerGame->room->update([
            'status' => RoomStatus::Active,
        ]);

        Log::info('StartGameJob: Game started', [
            'multiplayer_game_id' => $this->multiplayerGameId,
            'room_code' => $multiplayerGame->room->room_code
        ]);

        // Schedule the next question job
        $timePerQuestion = $multiplayerGame->room->settings->time_per_question ?? 30;
        NextQuestionJob::dispatch($this->multiplayerGameId)
            ->delay(now()->addSeconds($timePerQuestion));
    }
}
