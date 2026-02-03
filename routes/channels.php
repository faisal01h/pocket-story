<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('game-session.{sessionId}', function ($user, $sessionId) {
    $session = \App\Models\GameSession::find($sessionId);

    return $session && (int) $session->user_id === (int) $user->id;
});
