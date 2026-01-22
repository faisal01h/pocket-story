<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GamePlayController extends Controller
{
    public function show(\App\Models\Game $game)
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $game);

        // Find or create active session
        $session = $game->gameSessions()
            ->where('user_id', auth()->id())
            ->latest()
            ->first();

        if (!$session) {
            $startNode = $game->storyNodes()->where('is_start_node', true)->first();
            $session = $game->gameSessions()->create([
                'user_id' => auth()->id(),
                'current_node_id' => $startNode?->id,
                'mode' => $game->settings['default_mode'] ?? 'standard',
                'state_history' => [],
            ]);
        }

        $session->load('currentNode.choices');

        return \Inertia\Inertia::render('Games/Play/Show', [
            'game' => $game,
            'session' => $session,
            'currentNode' => $session->currentNode,
        ]);
    }

    public function action(Request $request, \App\Models\Game $game, \App\Services\GoogleGenAIService $aiService)
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $game);

        $validated = $request->validate([
            'session_id' => 'required|exists:game_sessions,id',
            'action_type' => 'required|string|in:choice,text', // choice (ID) or text (LLM input)
            'choice_id' => 'nullable|exists:choices,id', // standard choice
            'input_text' => 'nullable|string', // llm input
        ]);

        $session = \App\Models\GameSession::findOrFail($validated['session_id']);
        
        if ($session->user_id !== auth()->id()) {
            abort(403);
        }

        if ($validated['action_type'] === 'choice') {
            $choice = \App\Models\Choice::find($validated['choice_id']);
            if ($choice && $choice->target_node_id) {
                $session->update([
                    'current_node_id' => $choice->target_node_id,
                ]);
                
                // If in LLM mode, we still might want to append this to history
                if ($session->mode === 'llm') {
                     $history = $session->state_history ?? [];
                     $history[] = ['role' => 'user', 'content' => $choice->label];
                }
            }
        } elseif ($validated['action_type'] === 'text' && $session->mode === 'llm') {
            // LLM Generation
            $historyStr = json_encode($session->state_history); // naive history string
            
            // Get nearby nodes for context
            $contextNodes = $game->storyNodes()->take(5)->get()->toJson(); // Simplified context
            
            try {
                $result = $aiService->generateNextState($historyStr, $validated['input_text'], $contextNodes);
                
                $responseContent = isset($result['candidates'][0]['content']['parts'][0]['text']) 
                    ? json_decode($result['candidates'][0]['content']['parts'][0]['text'], true) 
                    : null;

                // Fallback parsing if JSON inside string
                if (!$responseContent && isset($result['candidates'][0]['content']['parts'][0]['text'])) {
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
                    ]);

                    return back()->with('dynamicState', $responseContent);
                }

            } catch (\Exception $e) {
                return back()->withErrors(['error' => 'AI Generation failed: ' . $e->getMessage()]);
            }
        }

        return redirect()->back();
    }
}
