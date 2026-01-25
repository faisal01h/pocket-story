<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedeemCode extends Model
{
    protected $fillable = [
        'code',
        'llm_model_id',
        'max_tokens',
        'period',
        'usage_limit',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function llmModel()
    {
        return $this->belongsTo(LlmModel::class);
    }

    public function usages()
    {
        return $this->hasMany(RedeemCodeUsage::class);
    }
}
