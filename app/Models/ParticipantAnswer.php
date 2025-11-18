<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantAnswer extends Model
{
    protected $fillable = [
        'multiplayer_game_id',
        'participant_id',
        'question_id',
        'selected_answer',
        'is_correct',
        'answered_at',
        'response_time_ms',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function multiplayerGame(): BelongsTo
    {
        return $this->belongsTo(MultiplayerGame::class, 'multiplayer_game_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(RoomParticipant::class, 'participant_id');
    }

    public function getQuestion(): ?array
    {
        $game = $this->multiplayerGame->game;
        $questions = $game->questions ?? [];
        
        // Find question by index or ID
        foreach ($questions as $index => $question) {
            if (isset($question['id']) && $question['id'] == $this->question_id) {
                return $question;
            }
            if ($index == $this->question_id) {
                return $question;
            }
        }
        
        return null;
    }

    // Helper methods
    public function getResponseTimeInSeconds(): float
    {
        return $this->response_time_ms / 1000;
    }

    public function calculateScore(): int
    {
        if (!$this->is_correct) {
            return 0;
        }

        // Standard scoring: base points with time bonus
        $basePoints = 100;
        $timeBonus = max(0, RoomSettings::DEFAULT_TIME_PER_QUESTION - $this->getResponseTimeInSeconds()) * 2;
        
        return (int) ($basePoints + $timeBonus);
    }

    // Scopes
    public function scopeForGame($query, int $multiplayerGameId)
    {
        return $query->where('multiplayer_game_id', $multiplayerGameId);
    }

    public function scopeForParticipant($query, int $participantId)
    {
        return $query->where('participant_id', $participantId);
    }

    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    public function scopeIncorrect($query)
    {
        return $query->where('is_correct', false);
    }
}
