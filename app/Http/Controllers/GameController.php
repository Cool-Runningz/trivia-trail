<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    /**
     * Show the game setup page
     *
     * @return Response
     */
    public function setup(): Response
    {
        return Inertia::render('game/setup');
    }

    /**
     * Show the game play page
     *
     * @return Response
     */
    public function play(): Response
    {
        return Inertia::render('game/play');
    }
}