<?php

namespace App\Services;

use App\Models\GameRoom;
use App\Models\RoomParticipant;
use App\Models\RoomSettings;
use App\Models\User;
use App\ParticipantStatus;
use App\RoomStatus;
use App\Utilities\RoomCodeGenerator;
use App\Utilities\RoomValidator;
use Illuminate\Support\Facades\DB;

class RoomService
{
    /**
     * Create a new game room.
     */
    public function createRoom(User $host, array $settings = []): GameRoom
    {
        // Check rate limiting
        if (!RoomValidator::canCreateRoom($host)) {
            throw new \Exception('You have reached the maximum number of room creations per hour. Please try again later.');
        }

        return DB::transaction(function () use ($host, $settings) {
            // Generate unique room code
            $roomCode = RoomCodeGenerator::generate();

            // Create the room
            $room = GameRoom::create([
                'room_code' => $roomCode,
                'host_user_id' => $host->id,
                'max_players' => $settings['max_players'] ?? 8,
                'current_players' => 1,
                'status' => RoomStatus::WAITING,
                'expires_at' => now()->addHours(24),
            ]);

            // Create room settings
            RoomSettings::create([
                'room_id' => $room->id,
                'time_per_question' => $settings['time_per_question'] ?? 30,
                'scoring_mode' => $settings['scoring_mode'] ?? 'standard',
                'category_id' => $settings['category_id'] ?? null,
                'difficulty' => $settings['difficulty'] ?? 'medium',
                'total_questions' => $settings['total_questions'] ?? 10,
            ]);

            // Add host as first participant
            RoomParticipant::create([
                'room_id' => $room->id,
                'user_id' => $host->id,
                'status' => ParticipantStatus::JOINED,
                'score' => 0,
                'joined_at' => now(),
            ]);

            // Increment creation count for rate limiting
            RoomValidator::incrementCreationCount($host);

            // Load relationships
            $room->load(['host', 'participants.user', 'settings']);

            return $room;
        });
    }

    /**
     * Join an existing room.
     */
    public function joinRoom(string $roomCode, User $user): GameRoom
    {
        $roomCode = RoomCodeGenerator::format($roomCode);

        // Find the room
        $room = GameRoom::where('room_code', $roomCode)->firstOrFail();

        // Validate if user can join
        if (!RoomValidator::canJoinRoom($room, $user)) {
            if ($room->status !== RoomStatus::WAITING) {
                throw new \Exception('This room is no longer accepting new participants.');
            }
            if (!RoomValidator::hasCapacity($room)) {
                throw new \Exception('This room is full.');
            }
            if ($room->participants()->where('user_id', $user->id)->exists()) {
                throw new \Exception('You are already in this room.');
            }
            throw new \Exception('Unable to join this room.');
        }

        return DB::transaction(function () use ($room, $user) {
            // Add participant
            RoomParticipant::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'status' => ParticipantStatus::JOINED,
                'score' => 0,
                'joined_at' => now(),
            ]);

            // Update room participant count
            $room->increment('current_players');

            // Load relationships
            $room->load(['host', 'participants.user', 'settings']);

            return $room;
        });
    }

    /**
     * Leave a room.
     */
    public function leaveRoom(GameRoom $room, User $user): void
    {
        DB::transaction(function () use ($room, $user) {
            // Find participant
            $participant = $room->participants()->where('user_id', $user->id)->first();

            if (!$participant) {
                throw new \Exception('You are not in this room.');
            }

            // Remove participant
            $participant->delete();

            // Update room participant count
            $room->decrement('current_players');

            // If host leaves, handle host transfer or room cleanup
            if (RoomValidator::isHost($room, $user)) {
                $this->handleHostLeaving($room);
            }

            // If room is empty, clean it up
            if ($room->current_players === 0) {
                $this->cleanupRoom($room);
            }
        });
    }

    /**
     * Handle host leaving the room.
     */
    private function handleHostLeaving(GameRoom $room): void
    {
        // If game is active, end the game
        if ($room->status === RoomStatus::ACTIVE) {
            $room->update(['status' => RoomStatus::CANCELLED]);
            return;
        }

        // If in waiting status and there are other participants, transfer host
        if ($room->status === RoomStatus::WAITING && $room->current_players > 0) {
            $newHost = $room->participants()
                ->where('user_id', '!=', $room->host_user_id)
                ->orderBy('joined_at')
                ->first();

            if ($newHost) {
                $room->update(['host_user_id' => $newHost->user_id]);
            }
        }
    }

    /**
     * Update participant status.
     */
    public function updateParticipantStatus(GameRoom $room, User $user, ParticipantStatus $status): void
    {
        $participant = $room->participants()->where('user_id', $user->id)->firstOrFail();
        $participant->update(['status' => $status]);
    }

    /**
     * Update room settings (host only).
     */
    public function updateSettings(GameRoom $room, User $user, array $settings): void
    {
        if (!RoomValidator::canModifySettings($room, $user)) {
            throw new \Exception('You do not have permission to modify room settings.');
        }

        // Validate settings
        $errors = RoomValidator::validateSettings($settings);
        if (!empty($errors)) {
            throw new \Exception('Invalid settings: ' . implode(', ', $errors));
        }

        DB::transaction(function () use ($room, $settings) {
            // Update room max_players if provided
            if (isset($settings['max_players'])) {
                $room->update(['max_players' => $settings['max_players']]);
            }

            // Update room settings
            $room->settings()->update(array_filter([
                'time_per_question' => $settings['time_per_question'] ?? null,
                'scoring_mode' => $settings['scoring_mode'] ?? null,
                'category_id' => $settings['category_id'] ?? null,
                'difficulty' => $settings['difficulty'] ?? null,
                'total_questions' => $settings['total_questions'] ?? null,
            ], fn($value) => $value !== null));
        });
    }

    /**
     * Clean up a room and its related data.
     */
    public function cleanupRoom(GameRoom $room): void
    {
        DB::transaction(function () use ($room) {
            // Delete all participants
            $room->participants()->delete();

            // Delete settings
            $room->settings()->delete();

            // Delete multiplayer game if exists
            if ($room->multiplayerGame) {
                $room->multiplayerGame->delete();
            }

            // Delete the room
            $room->delete();
        });
    }

    /**
     * Clean up expired rooms.
     */
    public function cleanupExpiredRooms(): int
    {
        $expiredRooms = GameRoom::where('expires_at', '<', now())
            ->whereIn('status', [RoomStatus::WAITING, RoomStatus::COMPLETED, RoomStatus::CANCELLED])
            ->get();

        foreach ($expiredRooms as $room) {
            $this->cleanupRoom($room);
        }

        return $expiredRooms->count();
    }

    /**
     * Get room by code.
     */
    public function getRoomByCode(string $roomCode): ?GameRoom
    {
        $roomCode = RoomCodeGenerator::format($roomCode);
        
        return GameRoom::where('room_code', $roomCode)
            ->with(['host', 'participants.user', 'settings'])
            ->first();
    }

    /**
     * Get active rooms for a user.
     */
    public function getUserActiveRooms(User $user)
    {
        return GameRoom::whereHas('participants', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->whereIn('status', [RoomStatus::WAITING, RoomStatus::ACTIVE])
        ->with(['host', 'participants.user', 'settings'])
        ->get();
    }
}
