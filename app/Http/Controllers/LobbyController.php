<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use App\RoomStatus;
use Illuminate\Http\Request;
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

        // Get public rooms that are waiting for players
        $rooms = GameRoom::where('status', RoomStatus::WAITING)
            ->where('current_players', '<', GameRoom::raw('max_players'))
            ->where('expires_at', '>', now())
            ->with(['host', 'settings', 'participants.user'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($room) {
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
            });

        return Inertia::render('multiplayer/Lobby', [
            'rooms' => $rooms,
            'categories' => $categories,
        ]);
    }
}
