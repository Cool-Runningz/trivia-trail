<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use App\Models\User;
use App\RoomStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class LobbyController extends Controller
{
    /**
     * Display the lobby with available rooms.
     */
    public function index(Request $request)
    {
        // Get trivia service for categories
        $triviaService = app(\App\Services\OpenTriviaService::class);
        $categories = $triviaService->getCategories();

        // Get user's active games (rooms they're participating in that aren't completed)
        $userActiveRooms = GameRoom::whereHas('participants', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->whereIn('status', [RoomStatus::WAITING, RoomStatus::ACTIVE])
            ->where('expires_at', '>', now())
            ->with(['host', 'settings', 'participants.user'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // No public room browsing - rooms are private and only accessible via room code
        $availableRooms = collect([]);

        // Map room data
        $mapRoom = function ($room) use ($request) {
            $isParticipant = $room->participants->contains('user_id', $request->user()->id);
            $isHost = $room->host_user_id === $request->user()->id;
            
            return [
                'id' => $room->id,
                'room_code' => $room->room_code,
                'host_user_id' => $room->host_user_id,
                'host' => [
                    'id' => $room->host->id,
                    'name' => $room->host->name,
                    'email' => $room->host->email,
                ],
                'max_players' => $room->max_players,
                'current_players' => $room->current_players,
                'status' => $room->status->value,
                'is_participant' => $isParticipant,
                'is_host' => $isHost,
                'settings' => [
                    'time_per_question' => $room->settings->time_per_question,
                    'category_id' => $room->settings->category_id,
                    'difficulty' => $room->settings->difficulty->value,
                    'total_questions' => $room->settings->total_questions,
                ],
                'participants' => $room->participants->map(function ($participant) {
                    return [
                        'id' => $participant->id,
                        'user' => [
                            'id' => $participant->user->id,
                            'name' => $participant->user->name,
                            'email' => $participant->user->email,
                        ],
                        'status' => $participant->status->value,
                        'score' => $participant->score,
                        'has_answered_current' => false,
                        'joined_at' => $participant->joined_at->toISOString(),
                    ];
                }),
                'expires_at' => $room->expires_at->toISOString(),
                'created_at' => $room->created_at->toISOString(),
                'updated_at' => $room->updated_at->toISOString(),
            ];
        };

        $rooms = $availableRooms->map($mapRoom);
        $activeGames = $userActiveRooms->map($mapRoom);

        // Get game history and format entries, filtering out any incomplete data
        $gameHistory = $this->getGameHistory($request->user())
            ->map(fn($room) => $this->formatHistoryEntry($room, $request->user()))
            ->filter() // Remove null entries from incomplete game data
            ->values()
            ->toArray();

        return Inertia::render('multiplayer/Lobby', [
            'rooms' => $rooms,
            'activeGames' => $activeGames,
            'categories' => $categories,
            'gameHistory' => $gameHistory,
        ]);
    }

    /**
     * Get game history for the authenticated user.
     * Returns completed games from the past 7 days where user was a participant.
     */
    protected function getGameHistory(User $user): Collection
    {
        return GameRoom::query()
            ->where('status', RoomStatus::COMPLETED)
            ->where('updated_at', '>=', now()->subDays(7))
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with([
                'participants.user:id,name,email',
                'multiplayerGame.game',
                'settings',
            ])
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();
    }

    /**
     * Format a game room into a history entry with all required fields.
     */
    protected function formatHistoryEntry(GameRoom $room, User $user): ?array
    {
        // Handle cases where required data is missing
        if (!$room->settings || !$room->multiplayerGame || !$room->multiplayerGame->game) {
            \Log::warning("Incomplete game data for room {$room->id}");
            return null;
        } 

        $rankedParticipants = $room->participants
            ->sortByDesc('score')
            ->values();

        // Calculate proper ranking with tie handling
        $userPosition = $this->calculateUserPosition($rankedParticipants, $user->id);



        return [
            'id' => $room->id,
            'room_code' => $room->room_code,
            'completed_at' => $room->updated_at->toISOString(),
            'participant_count' => $room->participants->count(),
            'user_position' => $userPosition,
            'total_questions' => $room->multiplayerGame->game->total_questions,
            'difficulty' => $room->settings->difficulty->value,
        ];
    }

    /**
     * Calculate user position with proper tie handling.
     * Players with the same score get the same rank.
     * Uses the same logic as MultiplayerGameService::generateLeaderboard
     */
    protected function calculateUserPosition($rankedParticipants, int $userId): int
    {
        $position = 1;
        $previousScore = null;
        $actualPosition = 1;

        foreach ($rankedParticipants as $participant) {
            // Handle ties - participants with same score get same position
            if ($previousScore !== null && $participant->score !== $previousScore) {
                $position = $actualPosition;
            }

            // If this is our user, return their rank
            if ($participant->user_id === $userId) {
                return $position;
            }

            $previousScore = $participant->score;
            $actualPosition++;
        }

        // Fallback - should not happen if user is in participants
        return $rankedParticipants->search(fn($p) => $p->user_id === $userId) + 1;
    }
}
