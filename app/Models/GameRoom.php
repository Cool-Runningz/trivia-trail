<?php

namespace App\Models;

use App\RoomStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameRoom extends Model
{
    protected $fillable = [
        'room_code',
        'host_user_id',
        'max_players',
        'current_players',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'status' => RoomStatus::class,
        'expires_at' => 'datetime',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(RoomParticipant::class, 'room_id');
    }

    public function settings(): HasOne
    {
        return $this->hasOne(RoomSettings::class, 'room_id');
    }

    public function multiplayerGame(): HasOne
    {
        return $this->hasOne(MultiplayerGame::class, 'room_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', [RoomStatus::WAITING, RoomStatus::ACTIVE]);
    }

    public function scopeByStatus($query, RoomStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        });
    }
}
