<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\LobbyController;
use App\Http\Controllers\RoomController;
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
        Route::get('/', function () {
            return redirect()->route('game.setup');
        })->name('index');
        Route::get('/setup', [GameController::class, 'setup'])->name('setup');
        Route::post('/', [GameController::class, 'store'])->name('store');
        
        // Test routes for UI preview (remove in production) - MUST come before /{game} routes
        Route::get('/test/play', function () {
            return Inertia::render('game/play', [
                'game' => [
                    'id' => 1,
                    'score' => 30,
                    'current_question_index' => 2,
                    'total_questions' => 10,
                    'difficulty' => 'medium',
                    'status' => 'active',
                ],
                'question' => [
                    'question' => 'What is the capital of France?',
                    'correct_answer' => 'Paris',
                    'incorrect_answers' => ['London', 'Berlin', 'Madrid'],
                    'difficulty' => 'easy',
                    'category' => 'Geography',
                    'type' => 'multiple',
                    'shuffled_answers' => ['Paris', 'London', 'Berlin', 'Madrid']
                ],
                'progress' => [
                    'current' => 3,
                    'total' => 10,
                    'percentage' => 30.0
                ]
            ]);
        })->name('test.play');
        
        Route::get('/test/results', function () {
            return Inertia::render('game/results', [
                'game' => [
                    'id' => 1,
                    'difficulty' => 'medium',
                    'total_questions' => 5,
                    'started_at' => '2024-01-01 10:00:00',
                    'completed_at' => '2024-01-01 10:05:00',
                    'time_taken_minutes' => 5,
                ],
                'results' => [
                    'final_score' => 80,
                    'correct_answers' => 4,
                    'total_questions' => 5,
                    'percentage_score' => 80.0,
                    'answer_breakdown' => [
                        [
                            'question' => 'What is 2+2?',
                            'selected_answer' => '4',
                            'correct_answer' => '4',
                            'is_correct' => true,
                            'points_earned' => 20,
                        ],
                        [
                            'question' => 'What is the capital of Spain?',
                            'selected_answer' => 'Barcelona',
                            'correct_answer' => 'Madrid',
                            'is_correct' => false,
                            'points_earned' => 0,
                        ],
                        [
                            'question' => 'What year did World War II end?',
                            'selected_answer' => '1945',
                            'correct_answer' => '1945',
                            'is_correct' => true,
                            'points_earned' => 20,
                        ],
                        [
                            'question' => 'What is the largest planet?',
                            'selected_answer' => 'Jupiter',
                            'correct_answer' => 'Jupiter',
                            'is_correct' => true,
                            'points_earned' => 20,
                        ],
                        [
                            'question' => 'Who painted the Mona Lisa?',
                            'selected_answer' => 'Leonardo da Vinci',
                            'correct_answer' => 'Leonardo da Vinci',
                            'is_correct' => true,
                            'points_earned' => 20,
                        ],
                    ]
                ]
            ]);
        })->name('test.results');
        
        // Game instance routes (MUST come after test routes)
        Route::get('/{game}', [GameController::class, 'show'])->name('show');
        Route::post('/{game}/answer', [GameController::class, 'answer'])->name('answer');
        Route::get('/{game}/results', [GameController::class, 'results'])->name('results');
    });
    
    // Multiplayer lobby (top-level for convenience)
    Route::get('/lobby', [LobbyController::class, 'index'])->name('lobby.index');
    
    // Multiplayer routes
    Route::prefix('multiplayer')->name('multiplayer.')->group(function () {
        // Room management
        Route::prefix('room')->name('room.')->group(function () {
            Route::post('/', [RoomController::class, 'store'])->name('store');
            Route::post('/join', [RoomController::class, 'join'])->name('join');
            Route::get('/{roomCode}', [RoomController::class, 'show'])->name('show');
            Route::post('/{roomCode}/start', [RoomController::class, 'start'])->name('start');
            Route::post('/{roomCode}/leave', [RoomController::class, 'leave'])->name('leave');
            Route::delete('/{roomCode}', [RoomController::class, 'destroy'])->name('destroy');
        });
        
        // Game flow
        Route::prefix('game')->name('game.')->group(function () {
            Route::get('/{roomCode}', [\App\Http\Controllers\MultiplayerGameController::class, 'show'])->name('show');
            Route::post('/{roomCode}/answer', [\App\Http\Controllers\MultiplayerGameController::class, 'answer'])->name('answer');
            Route::post('/{roomCode}/next', [\App\Http\Controllers\MultiplayerGameController::class, 'nextQuestion'])->name('next');
            Route::get('/{roomCode}/results', [\App\Http\Controllers\MultiplayerGameController::class, 'results'])->name('results');
        });
    });
});

require __DIR__.'/settings.php';
