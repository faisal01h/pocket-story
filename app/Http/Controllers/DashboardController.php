<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $stats = [
            'sessions_count' => $user->gameSessions()->count(),
            'unique_games_played' => $user->gameSessions()->distinct('game_id')->count('game_id'),
            'total_games_created' => $user->games()->count(),
        ];

        $recentSessions = $user->gameSessions()
            ->with(['game', 'currentNode'])
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'recentSessions' => $recentSessions,
        ]);
    }
}
