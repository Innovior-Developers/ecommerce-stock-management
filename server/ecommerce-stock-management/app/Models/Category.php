<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use App\Traits\MongoIdHelper;

class Category extends Model
{
    use MongoIdHelper; // ✅ Add this trait

    protected $connection = 'mongodb';
    protected $collection = 'categories';

    protected $fillable = [
        'name',
        'description',
        'slug',
        'status',
        'sort_order',
        'parent_id',
        'image_url',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        '_id' => 'string', // ✅ Add this
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ✅ UPDATE: Relationships with proper ID handling
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id', '_id');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id', '_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}