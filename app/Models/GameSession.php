<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{

    protected $fillable = [
        'user_id',
        'game_id',
        'current_node_id',
        'mode',
        'state_history',
    ];

    protected $casts = [
        'state_history' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function currentNode()
    {
        return $this->belongsTo(StoryNode::class, 'current_node_id');
    }

}
