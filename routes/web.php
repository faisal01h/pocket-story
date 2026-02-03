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

    // Subscription routes
    Route::get('subscription', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscription.index');
    Route::get('subscription/current', [\App\Http\Controllers\SubscriptionController::class, 'show'])->name('subscription.show');
    Route::post('subscription/subscribe', [\App\Http\Controllers\SubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
    Route::post('subscription/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('subscription.cancel');

    Route::resource('games', \App\Http\Controllers\GameController::class);
    Route::resource('story-nodes', \App\Http\Controllers\StoryNodeController::class)->only(['store', 'update', 'destroy']);
    Route::resource('games.play', \App\Http\Controllers\GamePlayController::class)->only(['index', 'store', 'show']);
    Route::post('games/{game}/play/{play}/action', [\App\Http\Controllers\GamePlayController::class, 'action'])->name('games.play.action');
    Route::post('games/{game}/play/{play}/restart', [\App\Http\Controllers\GamePlayController::class, 'restart'])->name('games.play.restart');
    Route::post('games/{game}/play/{play}/switch-mode', [\App\Http\Controllers\GamePlayController::class, 'switchMode'])->name('games.play.switch-mode');
    Route::post('games/{game}/play/{play}/regenerate', [\App\Http\Controllers\GamePlayController::class, 'regenerate'])->name('games.play.regenerate');
    Route::post('games/{game}/play/{play}/edit-response', [\App\Http\Controllers\GamePlayController::class, 'editResponse'])->name('games.play.edit-response');

    // Admin Routes
    Route::get('admin/llm-requests', [\App\Http\Controllers\Admin\LlmRequestController::class, 'index'])->name('admin.llm-requests.index');
    Route::get('admin/llm-requests/{llmRequest}', [\App\Http\Controllers\Admin\LlmRequestController::class, 'show'])->name('admin.llm-requests.show');
    Route::resource('admin/llm-providers', \App\Http\Controllers\Admin\LlmProviderController::class)->names([
        'index' => 'admin.llm-providers.index',
        'create' => 'admin.llm-providers.create',
        'store' => 'admin.llm-providers.store',
        'edit' => 'admin.llm-providers.edit',
        'update' => 'admin.llm-providers.update',
        'destroy' => 'admin.llm-providers.destroy',
    ]);
    Route::resource('admin/llm-models', \App\Http\Controllers\Admin\LlmModelController::class)->names([
        'index' => 'admin.llm-models.index',
        'create' => 'admin.llm-models.create',
        'store' => 'admin.llm-models.store',
        'edit' => 'admin.llm-models.edit',
        'update' => 'admin.llm-models.update',
        'destroy' => 'admin.llm-models.destroy',
    ]);
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
    Route::resource('admin/subscription-plans', \App\Http\Controllers\Admin\SubscriptionPlanController::class)->names([
        'index' => 'admin.subscription-plans.index',
        'create' => 'admin.subscription-plans.create',
        'store' => 'admin.subscription-plans.store',
        'edit' => 'admin.subscription-plans.edit',
        'update' => 'admin.subscription-plans.update',
        'destroy' => 'admin.subscription-plans.destroy',
    ]);

    Route::get('redeem', function () {
        return Inertia::render('Dashboard/Redeem');
    })->name('redeem.index');
    Route::post('redeem', [\App\Http\Controllers\RedeemCodeController::class, 'redeem'])->name('redeem.store');
});

require __DIR__.'/settings.php';
