<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LlmLimit extends Model
{
    protected $fillable = [
        'user_id',
        'llm_model_id',
        'period',
        'period_hours',
        'resets_at',
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resets_at' => 'datetime',
            'is_active' => 'boolean',
            'max_tokens' => 'integer',
            'period_hours' => 'integer',
        ];
    }
}
