<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Choice extends Model
{

    protected $fillable = [
        'story_node_id',
        'target_node_id',
        'label',
    ];

    public function storyNode()
    {
        return $this->belongsTo(StoryNode::class);
    }

    public function targetNode()
    {
        return $this->belongsTo(StoryNode::class, 'target_node_id');
    }

}
