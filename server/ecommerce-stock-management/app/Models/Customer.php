<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Str;

class Customer extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'customers';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'marketing_consent',
        'public_id',
        'date_of_birth', // ✅ Added
        'gender', // ✅ Added
        'preferences', // ✅ Added
    ];

    protected $hidden = [
        '_id', // ✅ Hide MongoDB ID
        'user_id', // ✅ Hide internal reference
    ];

    protected $casts = [
        'marketing_consent' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'date_of_birth' => 'date', // ✅ Added
        'preferences' => 'array', // ✅ Added
    ];

    // ✅ Generate public_id on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (!$customer->public_id) {
                $customer->public_id = 'cus_' . Str::random(20);
            }
        });
    }

    // ✅ Generate hashed public ID for frontend
    public function getHashedIdAttribute()
    {
        return 'cus_' . substr(hash('sha256', (string)$this->_id), 0, 16);
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', '_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id', '_id');
    }
}