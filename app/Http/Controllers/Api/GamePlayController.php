<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GameSessionResource;
use App\Models\Game;
use App\Models\GameSession;
use App\Services\GoogleGenAIService;
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
            'state_history' => [],
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

        $play->load('currentNode.choices');

        return new GameSessionResource($play);
    }

    public function action(Request $request, Game $game, GameSession $play, GoogleGenAIService $aiService): \Illuminate\Http\JsonResponse|GameSessionResource
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
                    $history = $play->state_history ?? [];
                    $history[] = ['role' => 'user', 'content' => $choiceLabel];
                    $play->update(['state_history' => $history]);
                }
            }
        } elseif ($validated['action_type'] === 'text' && $play->mode === 'llm') {
            $historyStr = json_encode($play->state_history);
            $contextNodes = $game->storyNodes()->take(5)->get()->toJson();

            try {
                $result = $aiService->generateNextState(
                    $historyStr,
                    $validated['input_text'],
                    $contextNodes,
                    $validated['model'] ?? null,
                    auth()->id(),
                    $play->id,
                    $game->llm_guidelines
                );

                $responseContent = null;
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    $raw = $result['candidates'][0]['content']['parts'][0]['text'];
                    $responseContent = json_decode($raw, true);

                    // Fallback if AI wrapped in markdown or just string
                    if (! $responseContent) {
                        $raw = preg_replace('/^```json/', '', $raw);
                        $raw = preg_replace('/```$/', '', $raw);
                        $responseContent = json_decode($raw, true);
                    }
                }

                if ($responseContent) {
                    $history = $play->state_history ?? [];
                    $history[] = ['role' => 'user', 'content' => $validated['input_text']];
                    $history[] = ['role' => 'model', 'content' => $responseContent['content']];

                    $play->update([
                        'current_node_id' => null,
                        'state_history' => $history,
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
        $play->update([
            'current_node_id' => $startNode?->id,
            'state_history' => [],
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
}
