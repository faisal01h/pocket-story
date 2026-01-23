<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LlmLimit extends Model
{
    protected $fillable = [
        'user_id',
        'model_name',
        'period',
        'max_tokens',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }}
