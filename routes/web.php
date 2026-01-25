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
    Route::post('games/{game}/play/{play}/regenerate', [\App\Http\Controllers\GamePlayController::class, 'regenerate'])->name('games.play.regenerate');

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
    Route::resource('admin/redeem-codes', \App\Http\Controllers\Admin\RedeemCodeController::class)->names([
        'index' => 'admin.redeem-codes.index',
        'create' => 'admin.redeem-codes.create',
        'store' => 'admin.redeem-codes.store',
        'edit' => 'admin.redeem-codes.edit',
        'update' => 'admin.redeem-codes.update',
        'destroy' => 'admin.redeem-codes.destroy',
    ]);
    Route::resource('admin/roles', \App\Http\Controllers\Admin\RoleController::class)->names([
        'index' => 'admin.roles.index',
        'create' => 'admin.roles.create',
        'store' => 'admin.roles.store',
        'edit' => 'admin.roles.edit',
        'update' => 'admin.roles.update',
        'destroy' => 'admin.roles.destroy',
    ]);
    Route::resource('admin/permissions', \App\Http\Controllers\Admin\PermissionController::class)->names([
        'index' => 'admin.permissions.index',
        'create' => 'admin.permissions.create',
        'store' => 'admin.permissions.store',
        'edit' => 'admin.permissions.edit',
        'update' => 'admin.permissions.update',
        'destroy' => 'admin.permissions.destroy',
    ]);
    Route::resource('admin/users', \App\Http\Controllers\Admin\UserController::class)->names([
        'index' => 'admin.users.index',
        'show' => 'admin.users.show',
        'edit' => 'admin.users.edit',
        'update' => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
    ]);
    Route::post('admin/users/{user}/summarize', [\App\Http\Controllers\Admin\UserController::class, 'summarize'])->name('admin.users.summarize');

    Route::get('redeem', function () {
        return \Inertia\Inertia::render('Dashboard/Redeem');
    })->name('redeem.index');
    Route::post('redeem', [\App\Http\Controllers\RedeemCodeController::class, 'redeem'])->name('redeem.store');
});

require __DIR__.'/settings.php';
