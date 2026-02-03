<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RedeemCodeUsageResource extends JsonResource
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
            'code' => $this->redeemCode->code,
            'llm_model' => $this->redeemCode->llmModel->name,
            'period' => $this->redeemCode->period,
            'max_tokens' => $this->redeemCode->max_tokens,
            'redeemed_at' => $this->created_at,
        ];
    }
}
