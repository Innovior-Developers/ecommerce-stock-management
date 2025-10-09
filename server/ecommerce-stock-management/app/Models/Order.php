<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;
use App\Traits\MongoIdHelper;

class Order extends Model
{
    use MongoIdHelper; // ✅ Add this trait

    protected $connection = 'mongodb';
    protected $collection = 'orders';

    protected $fillable = [
        'order_number',
        'customer_id',
        'items',
        'shipping_address',
        'billing_address',
        'payment',
        'status',
        'subtotal',
        'tax',
        'shipping_cost',
        'total',
        'notes',
        'tracking_number',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        '_id' => 'string', // ✅ Add this
        'items' => 'array',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'payment' => 'array',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Fixed relationship - should reference Customer, not User
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', '_id');
    }

    // Relationship to user through customer
    public function user()
    {
        return $this->hasOneThrough(
            User::class,
            Customer::class,
            '_id',      // Foreign key on customers table
            '_id',      // Foreign key on users table
            'customer_id', // Local key on orders table
            'user_id'   // Local key on customers table
        );
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
        return $query->where('status', 'delivered');
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // Methods
    public function calculateTotal()
    {
        $subtotal = collect($this->items)->sum(function ($item) {
            return $item['quantity'] * $item['unit_price'];
        });

        $this->subtotal = $subtotal;
        $this->tax = $subtotal * 0.1; // 10% tax
        $this->total = $this->subtotal + $this->tax + $this->shipping_cost;

        return $this->total;
    }
}