<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'settings',
        'llm_guidelines',
        'is_public',
        'slug',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_public' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($game) {
            if (empty($game->slug)) {
                $game->slug = $game->generateUniqueSlug($game->title);
            }
        });
    }

    public function generateUniqueSlug(?string $title): string
    {
        $slug = \Illuminate\Support\Str::slug($title ?: 'game');
        $original = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$original}-" . $count++;
        }

        return $slug;
    }

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
