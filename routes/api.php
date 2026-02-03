<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\GamePlayController;

use App\Http\Controllers\Api\LlmModelController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->as('api.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', [AuthController::class, 'user'])->name('user');

    Route::get('/llm-models', [LlmModelController::class, 'index'])->name('llm-models.index');
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::get('/redeem/history', [\App\Http\Controllers\Api\RedeemCodeController::class, 'history'])->name('redeem.history');

    Route::apiResource('games', GameController::class)->only(['index', 'show']);

    Route::prefix('games/{game}/play')->as('games.play.')->group(function () {
        Route::get('/', [GamePlayController::class, 'index'])->name('index');
        Route::post('/', [GamePlayController::class, 'store'])->name('store');
        Route::get('/{play}', [GamePlayController::class, 'show'])->name('show');
        Route::get('/{play}/history', [GamePlayController::class, 'history'])->name('history');
        Route::post('/{play}/action', [GamePlayController::class, 'action'])->name('action');
        Route::post('/{play}/regenerate', [GamePlayController::class, 'regenerate'])->name('regenerate');
        Route::post('/{play}/edit-response', [GamePlayController::class, 'editResponse'])->name('edit-response');
        Route::post('/{play}/restart', [GamePlayController::class, 'restart'])->name('restart');
        Route::post('/{play}/switch-mode', [GamePlayController::class, 'switchMode'])->name('switch-mode');
    });
});

// Xendit webhooks (no auth, uses signature verification)
Route::post('webhooks/xendit', [App\Http\Controllers\Webhooks\XenditWebhookController::class, 'handle'])->name('webhooks.xendit');
