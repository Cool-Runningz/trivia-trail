<?php

namespace App\Utilities;

use App\Models\GameRoom;
use App\Models\User;
use App\RoomStatus;
use Illuminate\Support\Facades\Cache;

class RoomValidator
{
    /**
     * Maximum number of rooms a user can create per hour.
     */
    private const MAX_ROOMS_PER_HOUR = 5;

    /**
     * Check if a room has capacity for additional participants.
     */
    public static function hasCapacity(GameRoom $room): bool
    {
        return $room->current_players < $room->max_players;
    }

    /**
     * Check if a user can join a specific room.
     */
    public static function canJoinRoom(GameRoom $room, User $user): bool
    {
        // Check if room is in waiting status
        if ($room->status !== RoomStatus::WAITING) {
            return false;
        }

        // Check if room has capacity
        if (!self::hasCapacity($room)) {
            return false;
        }

        // Check if user is already in the room
        if ($room->participants()->where('user_id', $user->id)->exists()) {
            return false;
        }

        // Check if room has expired
        if ($room->expires_at && now()->isAfter($room->expires_at)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a user can create a new room (rate limiting).
     */
    public static function canCreateRoom(User $user): bool
    {
        $cacheKey = "room_creation_limit:user_{$user->id}";
        $creationCount = Cache::get($cacheKey, 0);

        return $creationCount < self::MAX_ROOMS_PER_HOUR;
    }

    /**
     * Increment the room creation counter for rate limiting.
     */
    public static function incrementCreationCount(User $user): void
    {
        $cacheKey = "room_creation_limit:user_{$user->id}";
        $creationCount = Cache::get($cacheKey, 0);
        
        Cache::put($cacheKey, $creationCount + 1, now()->addHour());
    }

    /**
     * Check if a user is the host of a room.
     */
    public static function isHost(GameRoom $room, User $user): bool
    {
        return $room->host_user_id === $user->id;
    }

    /**
     * Check if a user can modify room settings.
     */
    public static function canModifySettings(GameRoom $room, User $user): bool
    {
        // Only host can modify settings
        if (!self::isHost($room, $user)) {
            return false;
        }

        // Can only modify settings in waiting status
        return $room->status === RoomStatus::WAITING;
    }

    /**
     * Check if a user can start a game.
     */
    public static function canStartGame(GameRoom $room, User $user): bool
    {
        // Only host can start game
        if (!self::isHost($room, $user)) {
            return false;
        }

        // Room must be in waiting status
        if ($room->status !== RoomStatus::WAITING) {
            return false;
        }

        // Must have at least 2 participants
        return $room->current_players >= 2;
    }

    /**
     * Check if a user is a participant in a room.
     */
    public static function isParticipant(GameRoom $room, User $user): bool
    {
        return $room->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user can access a room.
     */
    public static function canAccessRoom(GameRoom $room, User $user): bool
    {
        return self::isParticipant($room, $user);
    }

    /**
     * Validate room settings.
     */
    public static function validateSettings(array $settings): array
    {
        $errors = [];

        // Validate max_players
        if (isset($settings['max_players'])) {
            $maxPlayers = (int) $settings['max_players'];
            if ($maxPlayers < 2 || $maxPlayers > 20) {
                $errors['max_players'] = 'Maximum players must be between 2 and 20';
            }
        }

        // Validate time_per_question
        if (isset($settings['time_per_question'])) {
            $timePerQuestion = (int) $settings['time_per_question'];
            if ($timePerQuestion < 10 || $timePerQuestion > 60) {
                $errors['time_per_question'] = 'Time per question must be between 10 and 60 seconds';
            }
        }

        // Validate total_questions
        if (isset($settings['total_questions'])) {
            $totalQuestions = (int) $settings['total_questions'];
            if ($totalQuestions < 5 || $totalQuestions > 50) {
                $errors['total_questions'] = 'Total questions must be between 5 and 50';
            }
        }

        return $errors;
    }
}
