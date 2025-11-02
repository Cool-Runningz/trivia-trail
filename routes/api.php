<?php

use App\Http\Controllers\TriviaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/categories', [TriviaController::class, 'categories'])->name('api.trivia.categories');
    Route::get('/questions', [TriviaController::class, 'questions'])->name('api.trivia.questions');
});