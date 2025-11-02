<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
    
    // Game routes
    Route::prefix('game')->name('game.')->group(function () {
        Route::get('/setup', [GameController::class, 'setup'])->name('setup');
        Route::get('/play', [GameController::class, 'play'])->name('play');
    });
});

require __DIR__.'/settings.php';
