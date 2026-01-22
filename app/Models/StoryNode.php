<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoryNode extends Model
{

    protected $fillable = [
        'game_id',
        'title',
        'content',
        'is_start_node',
    ];

    protected $casts = [
        'is_start_node' => 'boolean',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function choices()
    {
        return $this->hasMany(Choice::class);
    }

}
