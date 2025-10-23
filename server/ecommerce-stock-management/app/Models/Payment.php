<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;
use App\Traits\MongoIdHelper;

class Payment extends Model
{
    use MongoIdHelper;

    protected $connection = 'mongodb';
    protected $collection = 'payments';

    protected $fillable = [
        'order_id',
        'user_id',
        'amount',
        'currency',
        'payment_method', // stripe, paypal, payhere
        'status', // pending, processing, completed, failed, refunded
        'gateway_transaction_id',
        'gateway_response',
        'metadata',
        'paid_at',
        'refunded_at',
        'refund_reason',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', '_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', '_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('payment_method', $gateway);
    }

    // Accessors
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsPendingAttribute(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function getCanRefundAttribute(): bool
    {
        return $this->status === 'completed' && !$this->refunded_at;
    }

    // Mutators
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = round((float) $value, 2);
    }
}
