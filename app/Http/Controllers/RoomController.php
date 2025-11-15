<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateRoomRequest;
use App\Http\Requests\JoinRoomRequest;
use App\Models\GameRoom;
use App\RoomStatus;
use App\Services\MultiplayerGameService;
use App\Services\RoomService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoomController extends Controller
{
    public function __construct(
        private RoomService $roomService,
        private MultiplayerGameService $gameService
    ) {}

    /**
     * Store a newly created room.
     */
    public function store(CreateRoomRequest $request)
    {
        try {
            $room = $this->roomService->createRoom(
                $request->user(),
                $request->validated()
            );

            return redirect()->route('multiplayer.room.show', $room->room_code)
                ->with('success', 'Room created successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Join an existing room.
     */
    public function join(JoinRoomRequest $request)
    {
        try {
            $room = $this->roomService->joinRoom(
                $request->input('room_code'),
                $request->user()
            );

            return redirect()->route('multiplayer.room.show', $room->room_code)
                ->with('success', 'Joined room successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified room.
     */
    public function show(Request $request, string $roomCode)
    {
        $room = $this->roomService->getRoomByCode($roomCode);

        if (!$room) {
            abort(404, 'Room not found');
        }

        // Check if user is a participant
        $isParticipant = $room->participants()
            ->where('user_id', $request->user()->id)
            ->exists();

        if (!$isParticipant) {
            return redirect()->route('lobby.index')
                ->withErrors(['error' => 'You are not a participant in this room.']);
        }

        // Check if user is the host
        $isHost = $room->host_user_id === $request->user()->id;

        // Prepare participant data with status indicators
        $participants = $room->participants->map(function ($participant) {
            return [
                'id' => $participant->id,
                'user' => [
                    'id' => $participant->user->id,
                    'name' => $participant->user->name,
                    'email' => $participant->user->email,
                ],
                'status' => $participant->status->value,
                'score' => $participant->score,
                'joined_at' => $participant->joined_at->toISOString(),
            ];
        });

        return Inertia::render('multiplayer/Room', [
            'room' => [
                'id' => $room->id,
                'room_code' => $room->room_code,
                'status' => $room->status->value,
                'max_players' => $room->max_players,
                'current_players' => $room->current_players,
                'host_user_id' => $room->host_user_id,
                'host' => [
                    'id' => $room->host->id,
                    'name' => $room->host->name,
                    'email' => $room->host->email,
                ],
                'expires_at' => $room->expires_at->toISOString(),
                'created_at' => $room->created_at->toISOString(),
                'updated_at' => $room->updated_at->toISOString(),
                'settings' => [
                    'time_per_question' => $room->settings->time_per_question,
                    'category_id' => $room->settings->category_id,
                    'difficulty' => $room->settings->difficulty->value,
                    'total_questions' => $room->settings->total_questions,
                ],
                'participants' => $participants,
            ],
            'participants' => $participants,
            'isHost' => $isHost,
            'canStart' => $isHost && $room->status === RoomStatus::WAITING && $room->current_players >= 2,
        ]);
    }

    /**
     * Start the game (host only).
     */
    public function start(Request $request, string $roomCode)
    {
        $room = $this->roomService->getRoomByCode($roomCode);

        if (!$room) {
            abort(404, 'Room not found');
        }

        // Verify user is the host
        if ($room->host_user_id !== $request->user()->id) {
            return back()->withErrors(['error' => 'Only the host can start the game.']);
        }

        // Verify room is in waiting status
        if ($room->status !== RoomStatus::WAITING) {
            return back()->withErrors(['error' => 'Game cannot be started in current room status.']);
        }

        // Verify minimum participants
        if ($room->current_players < 2) {
            return back()->withErrors(['error' => 'At least 2 players are required to start the game.']);
        }

        try {
            // Start the multiplayer game
            $multiplayerGame = $this->gameService->startGame($room);

            // Redirect directly to the game page
            return redirect()->route('multiplayer.game.show', $room->room_code)
                ->with('success', 'Game started!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Leave the room.
     */
    public function leave(Request $request, string $roomCode)
    {
        $room = $this->roomService->getRoomByCode($roomCode);

        if (!$room) {
            abort(404, 'Room not found');
        }

        try {
            $this->roomService->leaveRoom($room, $request->user());

            return redirect()->route('lobby.index')
                ->with('success', 'You have left the room.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
