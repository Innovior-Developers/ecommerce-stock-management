<?php

namespace App\Models;

use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Traits\MongoIdHelper;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, MongoIdHelper;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'avatar',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        '_id' => 'string',
        'email_verified_at' => 'datetime',
    ];

    // JWT methods
    public function getJWTIdentifier()
    {
        // Always return string representation of _id
        return (string) $this->_id;
    }

    public function getJWTCustomClaims()
    {
        // Return empty array to avoid claim issues
        return [];
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id', '_id');
    }
}