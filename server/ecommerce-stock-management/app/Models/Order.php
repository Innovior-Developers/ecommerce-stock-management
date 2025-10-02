<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'orders';

    protected $fillable = [
        'customer_id',
        'order_number',
        'status',
        'payment_status',
        'payment_method',
        'total_amount',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'items',
        'shipping_address',
        'billing_address',
        'notes',
        'public_id',
    ];

    protected $hidden = [
        '_id', // ✅ Hide MongoDB ID
        'customer_id', // ✅ Hide internal reference
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'items' => 'array',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ✅ Generate public_id and order_number on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->public_id) {
                $order->public_id = 'ord_' . Str::random(20);
            }

            if (!$order->order_number) {
                $order->order_number = 'ORD-' . strtoupper(Str::random(10));
            }
        });
    }

    // ✅ Generate hashed public ID for frontend
    public function getHashedIdAttribute()
    {
        return 'ord_' . substr(hash('sha256', (string)$this->_id), 0, 16);
    }

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', '_id');
    }
}
