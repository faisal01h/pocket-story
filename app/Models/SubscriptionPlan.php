<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_period',
        'max_tokens',
        'rate_limit_period_hours',
        'is_active',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price' => 'integer',
            'max_tokens' => 'integer',
            'rate_limit_period_hours' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get all user subscriptions for this plan.
     */
    public function userSubscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Scope a query to only include active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get formatted price for display.
     */
    public function getPriceFormatted(): string
    {
        if ($this->price === 0) {
            return 'Free';
        }

        // Format Indonesian Rupiah
        if ($this->currency === 'IDR') {
            return 'Rp '.number_format($this->price, 0, ',', '.');
        }

        return $this->currency.' '.number_format($this->price / 100, 2);
    }

    /**
     * Get rate limit period description.
     */
    public function getRateLimitDescription(): string
    {
        if ($this->rate_limit_period_hours) {
            return number_format($this->max_tokens).' tokens / '.$this->rate_limit_period_hours.' hours';
        }

        return number_format($this->max_tokens).' tokens / day';
    }
}
