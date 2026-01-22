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
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::resource('games', \App\Http\Controllers\GameController::class);
    Route::resource('story-nodes', \App\Http\Controllers\StoryNodeController::class)->only(['store', 'update', 'destroy']);
    Route::resource('games.play', \App\Http\Controllers\GamePlayController::class)->only(['show']);
    Route::post('games/{game}/play/action', [\App\Http\Controllers\GamePlayController::class, 'action'])->name('games.play.action');
});

require __DIR__.'/settings.php';
