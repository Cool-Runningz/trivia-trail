<?php

namespace App\Models;

use App\DifficultyLevel;
use App\ScoringMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomSettings extends Model
{
    protected $fillable = [
        'room_id',
        'time_per_question',
        'scoring_mode',
        'category_id',
        'difficulty',
        'total_questions',
    ];

    protected $casts = [
        'scoring_mode' => ScoringMode::class,
        'difficulty' => DifficultyLevel::class,
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class, 'room_id');
    }
}
