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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('category_id')->nullable(); // Open Trivia DB category ID
            $table->enum('difficulty', ['easy', 'medium', 'hard']);
            $table->integer('total_questions');
            $table->integer('current_question_index')->default(0);
            $table->integer('score')->default(0);
            $table->enum('status', ['active', 'completed'])->default('active');
            $table->json('questions')->nullable(); // Store API response for session
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
