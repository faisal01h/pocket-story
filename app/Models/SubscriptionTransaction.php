<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionTransaction extends Model
{
    protected $fillable = [
        'user_subscription_id',
        'amount',
        'currency',
        'status',
        'xendit_invoice_id',
        'xendit_invoice_url',
        'payment_method',
        'payment_channel',
        'paid_at',
        'failed_at',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'metadata' => 'array',
            'amount' => 'integer',
        ];
    }

    /**
     * Get the user subscription that owns this transaction.
     */
    public function userSubscription()
    {
        return $this->belongsTo(UserSubscription::class);
    }

    /**
     * Scope a query to only include paid transactions.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Mark the transaction as paid.
     */
    public function markAsPaid(): bool
    {
        $this->status = 'paid';
        $this->paid_at = now();

        return $this->save();
    }

    /**
     * Mark the transaction as failed.
     */
    public function markAsFailed(): bool
    {
        $this->status = 'failed';
        $this->failed_at = now();

        return $this->save();
    }
}
