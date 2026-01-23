<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('discover', [\App\Http\Controllers\DiscoveryController::class, 'index'])->name('discovery.index');
    Route::post('discover/join', [\App\Http\Controllers\DiscoveryController::class, 'join'])->name('discovery.join');

    Route::resource('games', \App\Http\Controllers\GameController::class);
    Route::resource('story-nodes', \App\Http\Controllers\StoryNodeController::class)->only(['store', 'update', 'destroy']);
    Route::resource('games.play', \App\Http\Controllers\GamePlayController::class)->only(['index', 'store', 'show']);
    Route::post('games/{game}/play/{play}/action', [\App\Http\Controllers\GamePlayController::class, 'action'])->name('games.play.action');
    Route::post('games/{game}/play/{play}/restart', [\App\Http\Controllers\GamePlayController::class, 'restart'])->name('games.play.restart');
    Route::post('games/{game}/play/{play}/switch-mode', [\App\Http\Controllers\GamePlayController::class, 'switchMode'])->name('games.play.switch-mode');

    // Admin Routes
    Route::get('admin/llm-requests', [\App\Http\Controllers\Admin\LlmRequestController::class, 'index'])->name('admin.llm-requests.index');
    Route::get('admin/llm-requests/{llmRequest}', [\App\Http\Controllers\Admin\LlmRequestController::class, 'show'])->name('admin.llm-requests.show');
    Route::resource('admin/llm-limits', \App\Http\Controllers\Admin\LlmLimitController::class)->names([
        'index' => 'admin.llm-limits.index',
        'create' => 'admin.llm-limits.create',
        'store' => 'admin.llm-limits.store',
        'edit' => 'admin.llm-limits.edit',
        'update' => 'admin.llm-limits.update',
        'destroy' => 'admin.llm-limits.destroy',
    ]);
});

require __DIR__.'/settings.php';
