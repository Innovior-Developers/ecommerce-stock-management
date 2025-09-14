<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Product extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'products';

    protected $fillable = [
        'name',
        'description',
        'price',
        'sku',
        'category',
        'stock_quantity',
        'status',
        'image_url',
        'weight',
        'dimensions',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'weight' => 'decimal:2',
        'dimensions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'product_id', '_id');
    }

    public function category_model()
    {
        return $this->belongsTo(Category::class, 'category', 'name');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeLowStock($query, $threshold = 10)
    {
        return $query->where('stock_quantity', '<=', $threshold);
    }

    // Accessors
    public function getIsInStockAttribute()
    {
        return $this->stock_quantity > 0;
    }

    public function getIsLowStockAttribute()
    {
        return $this->stock_quantity <= 10 && $this->stock_quantity > 0;
    }
}
