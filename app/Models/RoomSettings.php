<?php

namespace App\Models;

use App\DifficultyLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomSettings extends Model
{
    /**
     * Default time per question in seconds.
     */
    public const DEFAULT_TIME_PER_QUESTION = 20;

    protected $fillable = [
        'room_id',
        'time_per_question',
        'category_id',
        'difficulty',
        'total_questions',
    ];

    protected $casts = [
        'difficulty' => DifficultyLevel::class,
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class, 'room_id');
    }
}
