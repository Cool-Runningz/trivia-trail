<?php

namespace App\Jobs;

use App\Models\GameRoom;
use App\RoomStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupInactiveRoomsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('CleanupInactiveRoomsJob: Starting cleanup');

        // Clean up expired rooms (older than 24 hours)
        $expiredRooms = GameRoom::where('expires_at', '<', now())
            ->whereIn('status', [RoomStatus::Waiting, RoomStatus::Starting])
            ->get();

        foreach ($expiredRooms as $room) {
            Log::info('CleanupInactiveRoomsJob: Deleting expired room', [
                'room_id' => $room->id,
                'room_code' => $room->room_code,
                'status' => $room->status->value
            ]);

            $room->delete();
        }

        // Clean up completed rooms (older than 1 hour)
        $completedRooms = GameRoom::where('status', RoomStatus::Completed)
            ->where('updated_at', '<', now()->subHour())
            ->get();

        foreach ($completedRooms as $room) {
            Log::info('CleanupInactiveRoomsJob: Deleting completed room', [
                'room_id' => $room->id,
                'room_code' => $room->room_code
            ]);

            $room->delete();
        }

        // Clean up cancelled rooms immediately
        $cancelledRooms = GameRoom::where('status', RoomStatus::Cancelled)->get();

        foreach ($cancelledRooms as $room) {
            Log::info('CleanupInactiveRoomsJob: Deleting cancelled room', [
                'room_id' => $room->id,
                'room_code' => $room->room_code
            ]);

            $room->delete();
        }

        Log::info('CleanupInactiveRoomsJob: Cleanup completed', [
            'expired_count' => $expiredRooms->count(),
            'completed_count' => $completedRooms->count(),
            'cancelled_count' => $cancelledRooms->count()
        ]);
    }
}
