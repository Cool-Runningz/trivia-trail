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
        Route::post('/', [GameController::class, 'store'])->name('store');
        Route::get('/{game}', [GameController::class, 'show'])->name('show');
        Route::post('/{game}/answer', [GameController::class, 'answer'])->name('answer');
        Route::get('/{game}/results', [GameController::class, 'results'])->name('results');
    });
});

require __DIR__.'/settings.php';
