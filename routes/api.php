<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\GamePlayController;
use App\Http\Controllers\Api\LlmModelController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->as('api.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', [AuthController::class, 'user'])->name('user');

    Route::get('/llm-models', [LlmModelController::class, 'index'])->name('llm-models.index');

    Route::apiResource('games', GameController::class)->only(['index', 'show']);

    Route::prefix('games/{game}/play')->as('games.play.')->group(function () {
        Route::get('/', [GamePlayController::class, 'index'])->name('index');
        Route::post('/', [GamePlayController::class, 'store'])->name('store');
        Route::get('/{play}', [GamePlayController::class, 'show'])->name('show');
        Route::post('/{play}/action', [GamePlayController::class, 'action'])->name('action');
        Route::post('/{play}/regenerate', [GamePlayController::class, 'regenerate'])->name('regenerate');
        Route::post('/{play}/restart', [GamePlayController::class, 'restart'])->name('restart');
        Route::post('/{play}/switch-mode', [GamePlayController::class, 'switchMode'])->name('switch-mode');
    });
});
