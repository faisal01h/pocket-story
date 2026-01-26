<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GamePlayController extends Controller
{
    public function index(\App\Models\Game $game)
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $game);

        $sessions = $game->gameSessions()
            ->where('user_id', auth()->id())
            ->with('currentNode')
            ->latest()
            ->get();

        return \Inertia\Inertia::render('Games/Play/Sessions', [
            'game' => $game,
            'sessions' => $sessions,
        ]);
    }

    public function store(\App\Models\Game $game)
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $game);

        $startNode = $game->storyNodes()->where('is_start_node', true)->first();

        $session = $game->gameSessions()->create([
            'user_id' => auth()->id(),
            'current_node_id' => $startNode?->id,
            'mode' => $game->settings['default_mode'] ?? 'standard',
        ]);

        return redirect()->route('games.play.show', [$game->id, $session->id]);
    }

    public function show(\App\Models\Game $game, \App\Models\GameSession $play)
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $game);

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

        $models = \App\Models\LlmModel::where('is_active', true)
            ->with('provider')
            ->get();

        return \Inertia\Inertia::render('Games/Play/Show', [
            'game' => $game,
            'session' => $play,
            'currentNode' => $play->currentNode,
            'dynamicState' => $play->dynamic_state,
            'availableModels' => $models,
        ]);
    }

    public function action(Request $request, \App\Models\Game $game, \App\Models\GameSession $play)
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $game);

        if ($play->user_id !== auth()->id() || $play->game_id !== $game->id) {
            abort(403);
        }

        $validated = $request->validate([
            'action_type' => 'required|string|in:choice,text', // choice (ID/Target) or text (LLM input)
            'choice_id' => 'nullable|exists:choices,id', // standard choice
            'target_node_id' => 'nullable|exists:story_nodes,id', // direct target node
            'input_text' => 'nullable|string', // llm input
            'model' => 'nullable|string',
        ]);

        $session = $play;

        $targetNodeId = $validated['target_node_id'] ?? null;
        $choiceLabel = null;

        if (! $targetNodeId && ! empty($validated['choice_id'])) {
            $choice = \App\Models\Choice::find($validated['choice_id']);
            $targetNodeId = $choice?->target_node_id;
            $choiceLabel = $choice?->label;
        }

        if ($targetNodeId) {
            $session->update([
                'current_node_id' => $targetNodeId,
                'dynamic_state' => null, // Clear dynamic state when moving to a predefined node
            ]);

            // If in LLM mode, we still might want to append this to history
            if ($session->mode === 'llm' && $choiceLabel) {
                $session->stateHistories()->create([
                    'role' => 'user',
                    'content' => $choiceLabel,
                ]);
            }
        }
        // LLM Generation
        $history = $session->stateHistories();
        
        // If we have a summary, we only need the recent history (e.g., last 5 messages)
        if ($session->history_summary) {
            $history = $history->latest('id')->take(6)->get()->reverse();
        } else {
            $history = $history->get();
        }

        $historyStr = json_encode($history->map(fn ($item) => [
            'role' => $item->role,
            'content' => $item->content,
        ])->toArray());

        // Get nearby nodes for context
        $contextNodes = $game->storyNodes()->take(5)->get()->toJson(); // Simplified context

        try {
            $aiService = \App\Services\LlmServiceFactory::make($validated['model'] ?? null);

            $responseContent = $aiService->generateNextState(
                $historyStr,
                $validated['input_text'],
                $contextNodes,
                $validated['model'] ?? null,
                auth()->id(),
                $session->id,
                $game->llm_guidelines // Pass system prompt
            );

            if ($responseContent) {
                // Update session history
                $session->stateHistories()->createMany([
                    ['role' => 'user', 'content' => $validated['input_text']],
                    ['role' => 'model', 'content' => $responseContent['content']],
                ]);

                $session->update([
                    'current_node_id' => null, // Dynamic state
                    'dynamic_state' => $responseContent,
                ]);

                return back();
            }

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'AI Generation failed: '.$e->getMessage()]);
        }

        return redirect()->back();
    }

    public function restart(\App\Models\Game $game, \App\Models\GameSession $play)
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $game);

        if ($play->user_id !== auth()->id() || $play->game_id !== $game->id) {
            abort(403);
        }

        $startNode = $game->storyNodes()->where('is_start_node', true)->first();

        $play->stateHistories()->delete();

        $play->update([
            'current_node_id' => $startNode?->id,
            'dynamic_state' => null,
        ]);

        return redirect()->back();
    }

    public function switchMode(Request $request, \App\Models\Game $game, \App\Models\GameSession $play)
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $game);

        if ($play->user_id !== auth()->id() || $play->game_id !== $game->id) {
            abort(403);
        }

        $validated = $request->validate([
            'mode' => 'required|string|in:standard,llm',
        ]);

        $play->update([
            'mode' => $validated['mode'],
        ]);

        return redirect()->back();
    }

    public function regenerate(Request $request, \App\Models\Game $game, \App\Models\GameSession $play)
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $game);

        if ($play->user_id !== auth()->id() || $play->game_id !== $game->id) {
            abort(403);
        }

        if (! ($game->settings['allow_llm_regeneration'] ?? false)) {
            abort(403, 'Regeneration is not allowed for this game.');
        }

        $validated = $request->validate([
            'model' => 'nullable|string',
        ]);

        // Get the last model response, strictly by ID to avoid timestamp collisions
        $lastResponse = $play->stateHistories()->latest('id')->first();

        if ($lastResponse && $lastResponse->role === 'model') {
            // Delete the last model response from DB
            \Illuminate\Support\Facades\Log::info('Regenerating for session: '.$play->id.' - Deleting model response: '.$lastResponse->id);
            $lastResponse->delete();

            // Refresh the session's state history to ensure we have the correct items for regeneration
            $play->unsetRelation('stateHistories');
            $history = $play->stateHistories;

            // Get the user message that sparked the response we just deleted
            $lastUserMessage = $history->last();

            if ($lastUserMessage && $lastUserMessage->role === 'user') {
                \Illuminate\Support\Facades\Log::info('Found user message for regeneration: '.$lastUserMessage->id);

                // The prompt should include everything BEFORE this user message
                $historyWithoutLastQuery = $play->stateHistories()->where('id', '<', $lastUserMessage->id);
                
                if ($play->history_summary) {
                    $historyWithoutLast = $historyWithoutLastQuery->latest('id')->take(6)->get()->reverse();
                } else {
                    $historyWithoutLast = $historyWithoutLastQuery->get();
                }

                $historyStr = json_encode($historyWithoutLast->map(fn ($item) => [
                    'role' => $item->role,
                    'content' => $item->content,
                ])->toArray());

                $contextNodes = $game->storyNodes()->take(5)->get()->toJson();

                try {
                    // Prioritize model from request, fallback to last request for this session
                    $modelIdentifier = $validated['model'] ?? null;

                    if (! $modelIdentifier) {
                        $lastRequest = \App\Models\RemoteLlmRequest::where('game_session_id', $play->id)
                            ->whereNotNull('llm_model_id')
                            ->latest('id')
                            ->first();

                        $modelIdentifier = $lastRequest?->llmModel?->identifier;
                    }
                    \Illuminate\Support\Facades\Log::info('Using model for regeneration: '.($modelIdentifier ?: 'default'));

                    $aiService = \App\Services\LlmServiceFactory::make($modelIdentifier);

                    $responseContent = $aiService->generateNextState(
                        $historyStr,
                        $lastUserMessage->content,
                        $contextNodes,
                        $modelIdentifier,
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
                            'dynamic_state' => $responseContent,
                        ]);

                        \Illuminate\Support\Facades\Log::info('Regeneration successful for session: '.$play->id);

                        return back()->with('success', 'Response regenerated successfully.');
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Regeneration AI failed: '.$e->getMessage());

                    return back()->withErrors(['error' => 'Regeneration failed: '.$e->getMessage()]);
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('Regeneration fallthrough: last message is not user.', [
                    'session_id' => $play->id,
                    'last_role' => $lastUserMessage?->role,
                    'history_count' => $history->count(),
                ]);
            }
        } else {
            \Illuminate\Support\Facades\Log::warning('Regeneration fallthrough: last response is not model.', [
                'session_id' => $play->id,
                'last_role' => $lastResponse?->role,
            ]);
        }

        return redirect()->back();
    }
}
