<?php

namespace App;

enum GameStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
}
