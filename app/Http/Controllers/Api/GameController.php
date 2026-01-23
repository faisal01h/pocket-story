<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GameResource;
use App\Models\Game;
use Illuminate\Support\Facades\Gate;

class GameController extends Controller
{
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $games = Game::all();

        return GameResource::collection($games);
    }

    public function show(Game $game): GameResource
    {
        Gate::authorize('view', $game);

        return new GameResource($game->load('storyNodes.choices'));
    }
}
