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

        $timePerQuestion = $this->room->settings->time_per_question ?? RoomSettings::DEFAULT_TIME_PER_QUESTION;
        $elapsed = $this->question_started_at->diffInSeconds(now());
        
        return max(0, $timePerQuestion - $elapsed);
    }

    public function isQuestionTimeExpired(): bool
    {
        return $this->getTimeRemaining() <= 0;
    }

    public function isLastQuestion(): bool
    {
        $totalQuestions = $this->game->total_questions;
        return $this->current_question_index >= ($totalQuestions - 1);
    }

    public function getNextQuestionIndex(): ?int
    {
        $nextIndex = $this->current_question_index + 1;
        $totalQuestions = $this->game->total_questions;
        
        return $nextIndex < $totalQuestions ? $nextIndex : null;
    }

    /**
     * Calculate time remaining for current question
     * Consistent method for time calculation across the application
     *
     * @return int Time remaining in seconds
     */
    public function calculateTimeRemaining(): int
    {
        if (!$this->question_started_at) {
            return 0;
        }

        $timePerQuestion = $this->room->settings->time_per_question ?? RoomSettings::DEFAULT_TIME_PER_QUESTION;
        $elapsed = $this->question_started_at->diffInSeconds(now());
        
        return max(0, $timePerQuestion - $elapsed);
    }

    /**
     * Check if ready for next question progression
     * Ready when timer expired OR all active participants have answered
     *
     * @return bool True if ready to advance to next question
     */
    public function isReadyForNext(): bool
    {
        // Ready if timer expired
        $timeExpired = $this->calculateTimeRemaining() <= 0;
        
        // Ready if all players answered
        $allAnswered = app(\App\Services\MultiplayerGameService::class)
            ->allParticipantsAnswered($this);
        
        return $timeExpired || $allAnswered;
    }
}
