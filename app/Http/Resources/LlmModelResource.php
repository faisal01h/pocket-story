<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LlmModelResource extends JsonResource
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
            'name' => $this->name,
            'identifier' => $this->identifier,
            'provider' => [
                'id' => $this->provider->id,
                'name' => $this->provider->name,
                'slug' => $this->provider->slug,
            ],
            'is_active' => (bool) $this->is_active,
        ];
    }
}
