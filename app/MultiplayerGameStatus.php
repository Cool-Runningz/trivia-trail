<?php

enum MultiplayerGameStatus: string
{
    case WAITING = 'waiting';
    case ACTIVE = 'active';
    case SHOWING_RESULTS = 'showing_results';
    case COMPLETED = 'completed';
}