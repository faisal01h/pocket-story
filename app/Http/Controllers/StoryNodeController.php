<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StoryNodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'game_id' => 'required|exists:games,id',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'is_start_node' => 'boolean',
            'choices' => 'nullable|array',
            'choices.*.label' => 'required|string',
            'choices.*.target_node_id' => 'nullable|exists:story_nodes,id',
        ]);

        $game = \App\Models\Game::findOrFail($validated['game_id']);
        \Illuminate\Support\Facades\Gate::authorize('update', $game);

        $node = $game->storyNodes()->create([
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'is_start_node' => $validated['is_start_node'] ?? false,
        ]);

        if (!empty($validated['choices'])) {
            foreach ($validated['choices'] as $choiceData) {
                $node->choices()->create($choiceData);
            }
        }

        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\StoryNode $storyNode)
    {
        $game = $storyNode->game;
        \Illuminate\Support\Facades\Gate::authorize('update', $game);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'is_start_node' => 'boolean',
            'choices' => 'nullable|array',
            'choices.*.id' => 'nullable|exists:choices,id',
            'choices.*.label' => 'required|string',
            'choices.*.target_node_id' => 'nullable|exists:story_nodes,id',
        ]);

        $storyNode->update([
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'is_start_node' => $validated['is_start_node'] ?? false,
        ]);

        // Sync choices
        // For simplicity, we can delete explicit "removed" choices if we sent the full list, 
        // but here let's validly update existing ones or create new ones.
        // A better approach for full sync is to delete all and recreate or advanced diffing.
        // For now, let's assume the frontend sends the "definitive" list of choices to keep/add.
        
        $existingChoiceIds = collect($validated['choices'] ?? [])->pluck('id')->filter();
        $storyNode->choices()->whereNotIn('id', $existingChoiceIds)->delete();

        if (!empty($validated['choices'])) {
            foreach ($validated['choices'] as $choiceData) {
                if (isset($choiceData['id'])) {
                    $storyNode->choices()->where('id', $choiceData['id'])->update([
                        'label' => $choiceData['label'],
                        'target_node_id' => $choiceData['target_node_id'],
                    ]);
                } else {
                    $storyNode->choices()->create([
                        'label' => $choiceData['label'],
                        'target_node_id' => $choiceData['target_node_id'],
                    ]);
                }
            }
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\StoryNode $storyNode)
    {
        $game = $storyNode->game;
        \Illuminate\Support\Facades\Gate::authorize('update', $game);
        
        $storyNode->delete();
        
        return redirect()->back();
    }
}
