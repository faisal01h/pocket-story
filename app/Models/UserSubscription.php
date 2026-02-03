<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'status',
        'starts_at',
        'expires_at',
        'cancelled_at',
        'rate_limit_resets_at',
        'xendit_invoice_id',
        'xendit_customer_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'rate_limit_resets_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the subscription.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription plan.
     */
    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Get all transactions for this subscription.
     */
    public function transactions()
    {
        return $this->hasMany(SubscriptionTransaction::class);
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Check if the subscription is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->expires_at !== null && $this->expires_at->isPast());
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(): bool
    {
        $this->status = 'cancelled';
        $this->cancelled_at = now();

        return $this->save();
    }

    /**
     * Renew the subscription.
     */
    public function renew(\DateTime $expiresAt): bool
    {
        $this->status = 'active';
        $this->expires_at = $expiresAt;
        $this->cancelled_at = null;

        return $this->save();
    }

    /**
     * Check if rate limit needs to be reset.
     */
    public function shouldResetRateLimit(): bool
    {
        if (! $this->rate_limit_resets_at) {
            return false;
        }

        return $this->rate_limit_resets_at->isPast();
    }
}
