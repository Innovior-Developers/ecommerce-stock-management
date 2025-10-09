<?php

namespace App\Models;

use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    // ✅ These properties are the ONLY correct way to handle MongoDB IDs
    protected $primaryKey = '_id';
    protected $keyType = 'string';
    public $incrementing = false;

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

    /**
     * Auto-hash password on save. This is crucial for User::create().
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'status' => $this->status,
            'email' => $this->email,
            'name' => $this->name,
        ];
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id', '_id');
    }
}
