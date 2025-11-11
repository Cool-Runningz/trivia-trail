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
        Schema::create('player_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->integer('question_index'); // Position in questions array
            $table->text('question'); // Store question text for reference
            $table->string('selected_answer');
            $table->string('correct_answer');
            $table->boolean('is_correct');
            $table->integer('points_earned');
            $table->timestamp('answered_at')->useCurrent();
            $table->timestamps();
            
            $table->unique(['game_id', 'question_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_answers');
    }
};
