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
        Schema::create('game_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_code', 6)->unique();
            $table->foreignId('host_user_id')->constrained('users')->onDelete('cascade');
            $table->integer('max_players')->default(8);
            $table->integer('current_players')->default(0);
            $table->enum('status', ['waiting', 'active', 'completed', 'cancelled'])->default('waiting');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index('room_code');
            $table->index('status');
            $table->index('host_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_rooms');
    }
};
