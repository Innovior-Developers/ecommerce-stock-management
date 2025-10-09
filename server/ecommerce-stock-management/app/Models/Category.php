<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Category extends Model
{
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
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship for subcategories
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Relationship for parent category
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Scope for active categories
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}