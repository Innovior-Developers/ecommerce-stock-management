<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'avatar',
        'provider',
        'provider_id',
        'email_verified_at',
        'public_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        '_id', // ✅ Hide MongoDB _id from API responses
        'provider_id',
    ];

    // ✅ REMOVE $appends - we'll handle serialization differently
    // protected $appends = ['id'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (!$user->public_id) {
                $user->public_id = 'usr_' . Str::random(20);
            }
        });
    }

    // ═══════════════════════════════════════════════════════════
    // JWT METHODS - USE _id (MongoDB's native ID)
    // ═══════════════════════════════════════════════════════════

    /**
     * ✅ CRITICAL: Return MongoDB _id for JWT authentication
     */
    public function getJWTIdentifier()
    {
        return (string) $this->_id;
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'name' => $this->name,
            'status' => $this->status,
            'public_id' => $this->public_id,
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // LARAVEL AUTH METHODS - USE _id
    // ═══════════════════════════════════════════════════════════

    public function getAuthIdentifier()
    {
        return (string) $this->_id;
    }

    public function getAuthIdentifierName()
    {
        return '_id';
    }

    // ═══════════════════════════════════════════════════════════
    // API SERIALIZATION - Override toArray() instead
    // ═══════════════════════════════════════════════════════════

    /**
     * ✅ Override toArray to replace _id with public_id as 'id'
     */
    public function toArray()
    {
        $array = parent::toArray();

        // Remove _id from array (already hidden, but double-check)
        unset($array['_id']);

        // Add public_id as 'id'
        $array['id'] = $this->public_id;

        return $array;
    }

    // Relationships
    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id', '_id');
    }
}
