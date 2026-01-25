<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LlmModel extends Model
{
    protected $fillable = [
        'llm_provider_id',
        'name',
        'identifier',
        'endpoint_url',
        'is_active',
    ];

    public function provider(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LlmProvider::class, 'llm_provider_id');
    }

    public function llmLimits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LlmLimit::class);
    }
}
