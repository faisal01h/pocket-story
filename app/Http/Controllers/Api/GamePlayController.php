<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GameSessionResource;
use App\Models\Game;
use App\Models\GameSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GamePlayController extends Controller
{
    public function index(Game $game): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        Gate::authorize('view', $game);

        $sessions = $game->gameSessions()
            ->where('user_id', auth()->id())
            ->with('currentNode')
            ->latest()
            ->get();

        return GameSessionResource::collection($sessions);
    }

    public function store(Game $game): GameSessionResource
    {
        Gate::authorize('view', $game);

        $startNode = $game->storyNodes()->where('is_start_node', true)->first();

        $session = $game->gameSessions()->create([
            'user_id' => auth()->id(),
            'current_node_id' => $startNode?->id,
            'mode' => $game->settings['default_mode'] ?? 'standard',
        ]);

        return new GameSessionResource($session);
    }

    public function show(Game $game, GameSession $play): GameSessionResource
    {
        Gate::authorize('view', $game);

        if ($play->user_id !== auth()->id() || $play->game_id !== $game->id) {
            abort(403);
        }

        // Heal logic for sessions without a starting node
        if (! $play->current_node_id && ! $play->dynamic_state) {
            $startNode = $game->storyNodes()->where('is_start_node', true)->first();
            if ($startNode) {
                $play->update(['current_node_id' => $startNode->id]);
                $play->refresh();
            }
        }

        $play->load(['currentNode.choices', 'stateHistories']);

        return new GameSessionResource($play);
    }

    public function history(Game $game, GameSession $play): \Illuminate\Http\JsonResponse
    {
        Gate::authorize('view', $game);

        if ($play->user_id !== auth()->id() || $play->game_id !== $game->id) {
            abort(403);
        }

        $history = $play->stateHistories()
            ->latest()
            ->paginate();

        return response()->json($history);
    }

    public function action(Request $request, Game $game, GameSession $play): \Illuminate\Http\JsonResponse|GameSessionResource
    {
        Gate::authorize('view', $game);

        if ($play->user_id !== auth()->id() || $play->game_id !== $game->id) {
            abort(403);
        }

        $validated = $request->validate([
            'action_type' => 'required|string|in:choice,text',
            'choice_id' => 'nullable|exists:choices,id',
            'target_node_id' => 'nullable|exists:story_nodes,id',
            'input_text' => 'nullable|string',
            'model' => 'nullable|string',
        ]);

        if ($validated['action_type'] === 'choice') {
            $targetNodeId = $validated['target_node_id'] ?? null;
            $choiceLabel = null;

            if (! $targetNodeId && ! empty($validated['choice_id'])) {
                $choice = \App\Models\Choice::find($validated['choice_id']);
                $targetNodeId = $choice?->target_node_id;
                $choiceLabel = $choice?->label;
            }

            if ($targetNodeId) {
                $play->update([
                    'current_node_id' => $targetNodeId,
                    'dynamic_state' => null,
                ]);

                if ($play->mode === 'llm' && $choiceLabel) {
                    $play->stateHistories()->create([
                        'role' => 'user',
                        'content' => $choiceLabel,
                    ]);
                }
            }
        } elseif ($validated['action_type'] === 'text' && $play->mode === 'llm') {
            $history = $play->stateHistories;

            // Apply history logic consistent with web controller
            if ($play->history_summary) {
                $history = $play->stateHistories()->latest('id')->take(6)->get()->reverse();
            } else {
                $history = $play->stateHistories;
            }

            $historyStr = json_encode($history->map(fn ($item) => [
                'role' => $item->role,
                'content' => $item->content,
            ])->toArray());
            $contextNodes = $game->storyNodes()->take(5)->get()->toJson();

            try {
                $aiService = \App\Services\LlmServiceFactory::make($validated['model'] ?? null);

                // Check if streaming is enabled
                if (config('app.llm_communication_method') === 'websocket') {
                    // Create user message in history first
                    $play->stateHistories()->create([
                        'role' => 'user',
                        'content' => $validated['input_text'],
                    ]);

                    // Trigger streaming in background (response will be broadcasted)
                    $aiService->generateNextState(
                        $historyStr,
                        $validated['input_text'],
                        $contextNodes,
                        $validated['model'] ?? null,
                        auth()->id(),
                        $play->id,
                        $game->llm_guidelines
                    );

                    // Return immediately with streaming indicator
                    return response()->json([
                        'streaming' => true,
                        'session_id' => $play->id,
                        'message' => 'Response is being streamed via WebSocket',
                    ]);
                }

                $responseContent = $aiService->generateNextState(
                    $historyStr,
                    $validated['input_text'],
                    $contextNodes,
                    $validated['model'] ?? null,
                    auth()->id(),
                    $play->id,
                    $game->llm_guidelines
                );

                if ($responseContent) {
                    $play->stateHistories()->createMany([
                        ['role' => 'user', 'content' => $validated['input_text']],
                        ['role' => 'model', 'content' => $responseContent['content']],
                    ]);

                    $play->update([
                        'current_node_id' => null,
                        'dynamic_state' => $responseContent,
                    ]);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'AI Generation failed: '.$e->getMessage()], 500);
            }
        }

        return new GameSessionResource($play->load('currentNode.choices'));
    }

    public function restart(Game $game, GameSession $play): GameSessionResource
    {
        Gate::authorize('view', $game);

        if ($play->user_id !== auth()->id() || $play->game_id !== $game->id) {
            abort(403);
        }

        $startNode = $game->storyNodes()->where('is_start_node', true)->first();

        $play->stateHistories()->delete();

        $play->update([
            'current_node_id' => $startNode?->id,
            'dynamic_state' => null,
        ]);

        return new GameSessionResource($play->load('currentNode.choices'));
    }

    public function switchMode(Request $request, Game $game, GameSession $play): GameSessionResource
    {
        Gate::authorize('view', $game);

        if ($play->user_id !== auth()->id() || $play->game_id !== $game->id) {
            abort(403);
        }

        $validated = $request->validate([
            'mode' => 'required|string|in:standard,llm',
        ]);

        $play->update([
            'mode' => $validated['mode'],
        ]);

        return new GameSessionResource($play->load('currentNode.choices'));
    }

    public function regenerate(Request $request, Game $game, GameSession $play): \Illuminate\Http\JsonResponse|GameSessionResource
    {
        Gate::authorize('view', $game);

        if ($play->user_id !== auth()->id() || $play->game_id !== $game->id) {
            abort(403);
        }

        if (! ($game->settings['allow_llm_regeneration'] ?? false)) {
            return response()->json(['error' => 'Regeneration is not allowed for this game.'], 403);
        }

        if ($play->mode !== 'llm') {
            return response()->json(['error' => 'Regeneration is only available in LLM mode.'], 400);
        }

        $lastHistory = $play->stateHistories()->latest('id')->first();
        if (! $lastHistory || $lastHistory->role !== 'model') {
            return response()->json(['error' => 'No LLM response found to regenerate.'], 400);
        }

        $lastUserMessage = $play->stateHistories()
            ->where('role', 'user')
            ->where('id', '<', $lastHistory->id)
            ->latest('id')
            ->first();

        if (! $lastUserMessage) {
            return response()->json(['error' => 'No user input found to regenerate from.'], 400);
        }

        // Delete the last model response to regenerate it
        $lastHistory->delete();

        $history = $play->stateHistories;
        if ($play->history_summary) {
            $history = $play->stateHistories()->latest('id')->take(6)->get()->reverse();
        }

        $historyStr = json_encode($history->map(fn ($item) => [
            'role' => $item->role,
            'content' => $item->content,
        ])->toArray());

        $contextNodes = $game->storyNodes()->take(5)->get()->toJson();

        try {
            $validated = $request->validate([
                'model' => 'nullable|string',
            ]);

            $aiService = \App\Services\LlmServiceFactory::make($validated['model'] ?? null);

            // Check if streaming is enabled
            if (config('app.llm_communication_method') === 'websocket') {
                // Trigger streaming in background (response will be broadcasted)
                $aiService->generateNextState(
                    $historyStr,
                    $lastUserMessage->content,
                    $contextNodes,
                    $validated['model'] ?? null,
                    auth()->id(),
                    $play->id,
                    $game->llm_guidelines
                );

                // Return immediately with streaming indicator
                return response()->json([
                    'streaming' => true,
                    'session_id' => $play->id,
                    'message' => 'Response is being regenerated via WebSocket',
                ]);
            }

            $responseContent = $aiService->generateNextState(
                $historyStr,
                $lastUserMessage->content,
                $contextNodes,
                $validated['model'] ?? null,
                auth()->id(),
                $play->id,
                $game->llm_guidelines
            );

            if ($responseContent) {
                $play->stateHistories()->create([
                    'role' => 'model',
                    'content' => $responseContent['content'],
                ]);

                $play->update([
                    'current_node_id' => null,
                    'dynamic_state' => $responseContent,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'AI Generation failed: '.$e->getMessage()], 500);
        }

        return new GameSessionResource($play->load('currentNode.choices'));
    }
}
