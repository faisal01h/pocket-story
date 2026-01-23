<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DiscoveryController extends Controller
{
    public function index(Request $request)
    {
        $query = Game::where('is_public', true)->with('user');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', $search);
            });
        }

        $games = $query->latest()->paginate(12)->withQueryString();

        return Inertia::render('Discovery/Index', [
            'games' => $games,
            'filters' => $request->only(['search']),
        ]);
    }

    public function join(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = $request->get('code');

        $game = Game::where('slug', $code)
            ->orWhere('id', $code)
            ->first();

        if (!$game) {
            return back()->withErrors(['code' => 'Game not found.']);
        }

        // If it's a private game, ensure the user owns it
        if (!$game->is_public && $game->user_id !== auth()->id()) {
            return back()->withErrors(['code' => 'This game is private.']);
        }

        return redirect()->route('games.play.index', $game);
    }
}
