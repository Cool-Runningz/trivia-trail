<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_rooms', function (Blueprint $table) {
            // Add composite index for efficient history queries filtering by status and ordering by updated_at
            $table->index(['status', 'updated_at'], 'idx_game_rooms_status_updated');
        });

        Schema::table('room_participants', function (Blueprint $table) {
            // Add index for efficient user history lookups
            // Note: A unique constraint already exists on (room_id, user_id)
            // This index on (user_id, room_id) optimizes queries starting with user_id
            $table->index(['user_id', 'room_id'], 'idx_room_participants_user_room');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_rooms', function (Blueprint $table) {
            $table->dropIndex('idx_game_rooms_status_updated');
        });

        Schema::table('room_participants', function (Blueprint $table) {
            $table->dropIndex('idx_room_participants_user_room');
        });
    }
};
