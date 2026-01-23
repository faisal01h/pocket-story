<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'game_id' => $this->game_id,
            'current_node_id' => $this->current_node_id,
            'mode' => $this->mode,
            'state_history' => $this->state_history,
            'dynamic_state' => $this->dynamic_state,
            'current_node' => new StoryNodeResource($this->whenLoaded('currentNode')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
