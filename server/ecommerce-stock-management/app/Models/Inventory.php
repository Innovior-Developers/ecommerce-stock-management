<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;

class Inventory extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'inventory';

    protected $fillable = [
        'product_id',
        'location_id',
        'qty_on_hand',
        'qty_reserved',
        'qty_available',
        'reorder_level',
        'reorder_quantity',
        'last_movement_date',
    ];

    protected $casts = [
        'qty_on_hand' => 'integer',
        'qty_reserved' => 'integer',
        'qty_available' => 'integer',
        'reorder_level' => 'integer',
        'reorder_quantity' => 'integer',
        'last_movement_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($inventory) {
            $inventory->qty_available = $inventory->qty_on_hand - $inventory->qty_reserved;
        });
    }
}
