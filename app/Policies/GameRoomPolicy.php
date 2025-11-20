<?php

namespace App\Policies;

use App\Models\GameRoom;
use App\Models\User;

class GameRoomPolicy
{
    /**
     * Determine if the user can view the game room.
     * Users can view a game room if they were a participant.
     */
    public function view(User $user, GameRoom $room): bool
    {
        return $room->participants()
            ->where('user_id', $user->id)
            ->exists();
    }
}
