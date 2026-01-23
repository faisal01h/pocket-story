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
            'state_history' => [],
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

        $play->load('currentNode.choices');

        return \Inertia\Inertia::render('Games/Play/Show', [
            'game' => $game,
            'session' => $play,
            'currentNode' => $play->currentNode,
            'dynamicState' => $play->dynamic_state,
        ]);
    }

    public function action(Request $request, \App\Models\Game $game, \App\Models\GameSession $play, \App\Services\GoogleGenAIService $aiService)
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

        if ($validated['action_type'] === 'choice') {
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
                    $history = $session->state_history ?? [];
                    $history[] = ['role' => 'user', 'content' => $choiceLabel];
                    $session->update(['state_history' => $history]);
                }
            }
        } elseif ($validated['action_type'] === 'text' && $session->mode === 'llm') {
            // LLM Generation
            $historyStr = json_encode($session->state_history); // naive history string

            // Get nearby nodes for context
            $contextNodes = $game->storyNodes()->take(5)->get()->toJson(); // Simplified context

            try {
                $result = $aiService->generateNextState(
                    $historyStr,
                    $validated['input_text'],
                    $contextNodes,
                    $validated['model'] ?? null,
                    auth()->id(),
                    $session->id,
                    $game->llm_guidelines // Pass system prompt
                );

                $responseContent = isset($result['candidates'][0]['content']['parts'][0]['text'])
                    ? json_decode($result['candidates'][0]['content']['parts'][0]['text'], true)
                    : null;

                // Fallback parsing if JSON inside string
                if (! $responseContent && isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    // Try to strip markdown code blocks
                    $raw = $result['candidates'][0]['content']['parts'][0]['text'];
                    $raw = preg_replace('/^```json/', '', $raw);
                    $raw = preg_replace('/```$/', '', $raw);
                    $responseContent = json_decode($raw, true);
                }

                if ($responseContent) {
                    // Update session history
                    $history = $session->state_history ?? [];
                    $history[] = ['role' => 'user', 'content' => $validated['input_text']];
                    $history[] = ['role' => 'model', 'content' => $responseContent['content']];

                    $session->update([
                        'current_node_id' => null, // Dynamic state
                        'state_history' => $history,
                        'dynamic_state' => $responseContent,
                    ]);

                    return back();
                }

            } catch (\Exception $e) {
                return back()->withErrors(['error' => 'AI Generation failed: '.$e->getMessage()]);
            }
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
        $play->update([
            'current_node_id' => $startNode?->id,
            'state_history' => [],
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
}
