<?php

enum ParticipantStatus: string
{
    case JOINED = 'joined';
    case READY = 'ready';
    case PLAYING = 'playing';
    case FINISHED = 'finished';
    case DISCONNECTED = 'disconnected';
}