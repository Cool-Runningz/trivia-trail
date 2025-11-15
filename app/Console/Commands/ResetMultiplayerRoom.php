<?php

namespace App\Console\Commands;

use App\Models\GameRoom;
use App\ParticipantStatus;
use App\RoomStatus;
use Illuminate\Console\Command;

class ResetMultiplayerRoom extends Command
{
    protected $signature = 'multiplayer:reset-room {room_code?}';
    protected $description = 'Reset a multiplayer room to waiting status';

    public function handle()
    {
        $roomCode = $this->argument('room_code');

        if ($roomCode) {
            $room = GameRoom::where('room_code', $roomCode)->first();
        } else {
            // Get the most recent active room
            $room = GameRoom::whereIn('status', ['active', 'completed'])
                ->latest()
                ->first();
        }

        if (!$room) {
            $this->error('No room found!');
            return 1;
        }

        $this->info("Resetting room: {$room->room_code}");

        // Reset room status
        $room->update(['status' => RoomStatus::WAITING]);

        // Reset participants
        $room->participants()->update(['status' => ParticipantStatus::JOINED, 'score' => 0]);

        // Delete multiplayer game if exists
        if ($room->multiplayerGame) {
            $this->info('Deleting multiplayer game...');
            $room->multiplayerGame->game()->delete();
            $room->multiplayerGame->delete();
        }

        $this->info("✅ Room {$room->room_code} has been reset to waiting status!");
        $this->info("You can now start a new game from the room lobby.");

        return 0;
    }
}
