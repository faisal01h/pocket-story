<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LlmProvider extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'base_url',
        'api_key',
        'secret_key',
        'is_active',
    ];

    public function models(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LlmModel::class);
    }
}
