<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;
use App\Traits\MongoIdHelper;
use App\Services\QuerySanitizer;

class Inventory extends Model
{
    use MongoIdHelper;

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
        '_id' => 'string',
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
        return $this->belongsTo(Product::class, 'product_id', '_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($inventory) {
            $inventory->qty_available = $inventory->qty_on_hand - $inventory->qty_reserved;

            $productId = QuerySanitizer::sanitizeMongoId($inventory->product_id);
            if (!$productId) {
                throw new \Exception('Invalid product_id format');
            }

            if (!Product::where('_id', $productId)->exists()) {
                throw new \Exception('Product with ID ' . $productId . ' does not exist');
            }
        });
    }
}