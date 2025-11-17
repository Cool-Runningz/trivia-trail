<?php

namespace App\Console\Commands;

use App\Models\GameRoom;
use App\Services\RoomService;
use Illuminate\Console\Command;

class DeleteRoom extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'multiplayer:delete-room {room_code? : The room code to delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a specific multiplayer room by room code';

    /**
     * Execute the console command.
     */
    public function handle(RoomService $roomService)
    {
        $roomCode = $this->argument('room_code');

        // If no room code provided, show list of active rooms
        if (!$roomCode) {
            $rooms = GameRoom::with('host')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            if ($rooms->isEmpty()) {
                $this->info('No rooms found.');
                return 0;
            }

            $this->table(
                ['Room Code', 'Host', 'Status', 'Players', 'Created'],
                $rooms->map(fn($room) => [
                    $room->room_code,
                    $room->host->name,
                    $room->status->value,
                    "{$room->current_players}/{$room->max_players}",
                    $room->created_at->diffForHumans(),
                ])
            );

            $roomCode = $this->ask('Enter room code to delete (or press enter to cancel)');
            
            if (!$roomCode) {
                $this->info('Cancelled.');
                return 0;
            }
        }

        // Find and delete the room
        $room = GameRoom::where('room_code', strtoupper($roomCode))->first();

        if (!$room) {
            $this->error("Room '{$roomCode}' not found.");
            return 1;
        }

        if (!$this->confirm("Delete room {$room->room_code} (Host: {$room->host->name})?", true)) {
            $this->info('Cancelled.');
            return 0;
        }

        try {
            $roomService->cleanupRoom($room);
            $this->info("✓ Room {$roomCode} deleted successfully.");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to delete room: {$e->getMessage()}");
            return 1;
        }
    }
}
