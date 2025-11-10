<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;
use App\Traits\MongoIdHelper;

class PaymentMethod extends Model
{
    use MongoIdHelper;

    protected $connection = 'mongodb';
    protected $collection = 'payment_methods';

    protected $fillable = [
        'user_id',
        'gateway', // stripe, paypal, payhere
        'type', // card, bank_account, paypal_account
        'is_default',
        'last_four',
        'expiry_date',
        'card_brand', // visa, mastercard, amex, etc.
        'gateway_customer_id',
        'gateway_payment_method_id',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'metadata' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', '_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    // Methods
    public function setAsDefault(): bool
    {
        // Remove default flag from other payment methods
        static::where('user_id', $this->user_id)
            ->where('_id', '!=', $this->_id)
            ->update(['is_default' => false]);

        $this->is_default = true;
        return $this->save();
    }

    public function remove(): bool
    {
        // If this is default, set another as default
        if ($this->is_default) {
            $nextDefault = static::where('user_id', $this->user_id)
                ->where('_id', '!=', $this->_id)
                ->first();

            if ($nextDefault) {
                $nextDefault->setAsDefault();
            }
        }

        return $this->delete();
    }
}