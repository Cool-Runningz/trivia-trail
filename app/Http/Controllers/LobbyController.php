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
        // Get public rooms that are waiting for players
        $availableRooms = GameRoom::where('status', RoomStatus::WAITING)
            ->where('current_players', '<', GameRoom::raw('max_players'))
            ->where('expires_at', '>', now())
            ->with(['host', 'settings'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($room) {
                return [
                    'id' => $room->id,
                    'room_code' => $room->room_code,
                    'host_name' => $room->host->name,
                    'current_players' => $room->current_players,
                    'max_players' => $room->max_players,
                    'difficulty' => $room->settings->difficulty,
                    'total_questions' => $room->settings->total_questions,
                    'category_id' => $room->settings->category_id,
                    'created_at' => $room->created_at->diffForHumans(),
                ];
            });

        // Get user's active rooms
        $userActiveRooms = GameRoom::whereHas('participants', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })
        ->whereIn('status', [RoomStatus::WAITING, RoomStatus::STARTING, RoomStatus::ACTIVE])
        ->with(['host', 'settings'])
        ->get()
        ->map(function ($room) use ($request) {
            $isHost = $room->host_user_id === $request->user()->id;
            
            return [
                'id' => $room->id,
                'room_code' => $room->room_code,
                'status' => $room->status->value,
                'host_name' => $room->host->name,
                'is_host' => $isHost,
                'current_players' => $room->current_players,
                'max_players' => $room->max_players,
                'difficulty' => $room->settings->difficulty,
                'total_questions' => $room->settings->total_questions,
            ];
        });

        return Inertia::render('multiplayer/lobby', [
            'availableRooms' => $availableRooms,
            'userActiveRooms' => $userActiveRooms,
        ]);
    }
}
