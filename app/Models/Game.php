<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function storyNodes()
    {
        return $this->hasMany(StoryNode::class);
    }

    public function gameSessions()
    {
        return $this->hasMany(GameSession::class);
    }

}
