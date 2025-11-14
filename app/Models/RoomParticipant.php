<?php

namespace App\Models;

use App\ParticipantStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomParticipant extends Model
{
    protected $fillable = [
        'room_id',
        'user_id',
        'status',
        'score',
        'joined_at',
    ];

    protected $casts = [
        'status' => ParticipantStatus::class,
        'joined_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class, 'room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ParticipantAnswer::class, 'participant_id');
    }

    // Methods for score tracking and status management
    public function addScore(int $points): void
    {
        $this->increment('score', $points);
    }

    public function updateStatus(ParticipantStatus $status): void
    {
        $this->update(['status' => $status]);
    }

    public function isHost(): bool
    {
        return $this->room->host_user_id === $this->user_id;
    }

    public function isConnected(): bool
    {
        return $this->status !== ParticipantStatus::DISCONNECTED;
    }

    // Scopes
    public function scopeByStatus($query, ParticipantStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeConnected($query)
    {
        return $query->where('status', '!=', ParticipantStatus::DISCONNECTED);
    }

    public function scopeInRoom($query, int $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeOrderByScore($query, string $direction = 'desc')
    {
        return $query->orderBy('score', $direction);
    }
}
