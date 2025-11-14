<?php

namespace App\Models;

use App\MultiplayerGameStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MultiplayerGame extends Model
{
    protected $fillable = [
        'room_id',
        'game_id',
        'current_question_index',
        'question_started_at',
        'status',
    ];

    protected $casts = [
        'status' => MultiplayerGameStatus::class,
        'question_started_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class, 'room_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function participantAnswers(): HasMany
    {
        return $this->hasMany(ParticipantAnswer::class, 'multiplayer_game_id');
    }

    // Helper methods
    public function getCurrentQuestion(): ?array
    {
        $questions = $this->game->questions ?? [];
        return $questions[$this->current_question_index] ?? null;
    }

    public function currentQuestion(): ?array
    {
        return $this->getCurrentQuestion();
    }

    public function hasMoreQuestions(): bool
    {
        $questions = $this->game->questions ?? [];
        return $this->current_question_index < count($questions) - 1;
    }

    public function nextQuestion(): void
    {
        $this->increment('current_question_index');
        $this->update(['question_started_at' => now()]);
    }

    public function getTimeRemaining(): int
    {
        if (!$this->question_started_at) {
            return 0;
        }

        $timePerQuestion = $this->room->settings->time_per_question ?? 30;
        $elapsed = now()->diffInSeconds($this->question_started_at);
        
        return max(0, $timePerQuestion - $elapsed);
    }

    public function isQuestionTimeExpired(): bool
    {
        return $this->getTimeRemaining() <= 0;
    }
}
