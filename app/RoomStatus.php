<?php

namespace App;

enum RoomStatus: string
{
    case WAITING = 'waiting';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}