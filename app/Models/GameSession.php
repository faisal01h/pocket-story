<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_id',
        'current_node_id',
        'mode',
        'dynamic_state',
        'history_summary',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\GameSessionMemory, \App\Models\GameSession>
     */
    public function memories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GameSessionMemory::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\GameSessionKnowledge, \App\Models\GameSession>
     */
    public function knowledges(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GameSessionKnowledge::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dynamic_state' => 'array',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, \App\Models\GameSession>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Game, \App\Models\GameSession>
     */
    public function game(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\StoryNode, \App\Models\GameSession>
     */
    public function currentNode(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(StoryNode::class, 'current_node_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\GameSessionStateHistory, \App\Models\GameSession>
     */
    public function stateHistories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GameSessionStateHistory::class);
    }
}
