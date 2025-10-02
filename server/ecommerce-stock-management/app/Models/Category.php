<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'categories';

    protected $fillable = [
        'name',
        'description',
        'slug',
        'parent_id',
        'image_url',
        'status',
        'sort_order',
        'meta_title',
        'meta_description',
        'public_id',
    ];

    protected $hidden = [
        '_id', // ✅ Hide MongoDB ID
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ✅ Generate public_id on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (!$category->public_id) {
                $category->public_id = 'cat_' . Str::random(20);
            }

            // Auto-generate slug if not provided
            if (!$category->slug && $category->name) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // ✅ Generate hashed public ID for frontend
    public function getHashedIdAttribute()
    {
        return 'cat_' . substr(hash('sha256', (string)$this->_id), 0, 16);
    }

    // Relationships
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id', '_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id', '_id');
    }
}
