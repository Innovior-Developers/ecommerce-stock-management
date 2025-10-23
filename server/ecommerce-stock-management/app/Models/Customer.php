<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;
use MongoDB\BSON\ObjectId; // ✅ Add this import
use App\Traits\MongoIdHelper;

class Customer extends Model
{
    use MongoIdHelper;

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
        'country',
        'postal_code',
        'date_of_birth',
        'gender',
        'addresses',
        'preferences',
        'marketing_consent',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'addresses' => 'array',
        'preferences' => 'array',
        'marketing_consent' => 'boolean',
    ];

    /**
     * Boot method to force _id generation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            // Ensure _id is generated before save
            if (!isset($customer->_id)) {
                $customer->_id = new ObjectId(); // ✅ Now properly imported
            }
        });
    }

    /**
     * Relationship: Customer belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id', '_id');
    }

    /**
     * Accessor: Full Name
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
