<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RemoteLlmRequest extends Model
{
    protected $fillable = [
        'user_id',
        'game_session_id',
        'provider',
        'model_name',
        'input_token',
        'output_token',
        'input_token_count',
        'output_token_count',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gameSession()
    {
        return $this->belongsTo(GameSession::class);
    }
}
