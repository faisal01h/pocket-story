<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return \Inertia\Inertia::render('Games/Index', [
            'games' => \App\Models\Game::where('user_id', auth()->id())->latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return \Inertia\Inertia::render('Games/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        $game = $request->user()->games()->create($validated);

        return redirect()->route('games.show', $game);
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\Game $game)
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $game);
        
        $game->load(['storyNodes.choices']);

        return \Inertia\Inertia::render('Games/Editor/Show', [
            'game' => $game,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(\App\Models\Game $game)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $game);
        return \Inertia\Inertia::render('Games/Edit', [
            'game' => $game
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\Game $game)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $game);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        $game->update($validated);

        return redirect()->route('games.show', $game);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\Game $game)
    {
        \Illuminate\Support\Facades\Gate::authorize('delete', $game);
        $game->delete();
        return redirect()->route('games.index');
    }
}
