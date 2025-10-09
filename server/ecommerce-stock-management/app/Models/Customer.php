<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\HasMany;

class Customer extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'customers';

    // ✅ These properties are the ONLY correct way to handle MongoDB IDs
    protected $primaryKey = '_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'marketing_consent',
    ];

    protected $casts = [
        '_id' => 'string',
        'user_id' => 'string',
        'marketing_consent' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', '_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id', '_id');
    }
}
