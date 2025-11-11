<?php

namespace App\Models;

use App\DifficultyLevel;
use App\GameStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'difficulty',
        'total_questions',
        'current_question_index',
        'score',
        'status',
        'questions',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'questions' => 'array',
        'status' => GameStatus::class,
        'difficulty' => DifficultyLevel::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function playerAnswers(): HasMany
    {
        return $this->hasMany(PlayerAnswer::class);
    }

    public function currentQuestion(): ?array
    {
        if (!$this->questions || $this->current_question_index >= count($this->questions)) {
            return null;
        }

        return $this->questions[$this->current_question_index];
    }

    public function isCompleted(): bool
    {
        return $this->status === GameStatus::Completed;
    }

    public function calculateFinalScore(): int
    {
        return $this->playerAnswers()->sum('points_earned');
    }

    public function getCorrectAnswersCount(): int
    {
        return $this->playerAnswers()->where('is_correct', true)->count();
    }

    public function getPercentageScore(): float
    {
        if ($this->total_questions === 0) {
            return 0.0;
        }

        return round(($this->getCorrectAnswersCount() / $this->total_questions) * 100, 1);
    }
}
