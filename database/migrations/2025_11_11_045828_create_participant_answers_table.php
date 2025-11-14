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
        Schema::create('participant_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('multiplayer_game_id')->constrained('multiplayer_games')->onDelete('cascade');
            $table->foreignId('participant_id')->constrained('room_participants')->onDelete('cascade');
            $table->integer('question_id'); // Index or identifier of question in game's questions array
            $table->text('selected_answer');
            $table->boolean('is_correct');
            $table->timestamp('answered_at');
            $table->integer('response_time_ms');
            $table->timestamps();
            
            $table->index(['multiplayer_game_id', 'participant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participant_answers');
    }
};
