<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSessionKnowledge extends Model
{
    protected $table = 'game_session_knowledges';
    protected $fillable = [
        'game_session_id',
        'key',
        'content',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\GameSession, \App\Models\GameSessionKnowledge>
     */
    public function gameSession(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }
}
