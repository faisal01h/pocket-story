<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LlmLimit extends Model
{
    protected $fillable = [
        'user_id',
        'llm_model_id',
        'period',
        'max_tokens',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function llmModel()
    {
        return $this->belongsTo(LlmModel::class);
    }
}
