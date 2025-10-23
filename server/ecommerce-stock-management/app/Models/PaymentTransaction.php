<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;
use App\Traits\MongoIdHelper;

class PaymentTransaction extends Model
{
    use MongoIdHelper;

    protected $connection = 'mongodb';
    protected $collection = 'payment_transactions';

    protected $fillable = [
        'payment_id',
        'transaction_type', // authorize, capture, refund, void
        'amount',
        'currency',
        'status', // success, failed, pending
        'gateway_transaction_id',
        'gateway_response',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', '_id');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
