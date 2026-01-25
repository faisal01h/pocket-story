<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedeemCodeUsage extends Model
{
    protected $fillable = [
        'redeem_code_id',
        'user_id',
    ];

    public function redeemCode()
    {
        return $this->belongsTo(RedeemCode::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
