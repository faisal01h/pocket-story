<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSessionStateHistory extends Model
{
    protected $fillable = [
        'game_session_id',
        'role',
        'content',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\GameSession, \App\Models\GameSessionStateHistory>
     */
    public function gameSession(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }
}
