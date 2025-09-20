<?php

namespace App\Models;

use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Carbon\Carbon;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'email_verified_at',
        'provider',
        'provider_id',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $dates = [
        'email_verified_at',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'status' => $this->status,
            'email' => $this->email,
            'name' => $this->name,
        ];
    }

    // Relationships
    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id', '_id');
    }

    /**
     * Set the email verified at timestamp
     */
    public function setEmailVerifiedAtAttribute($value)
    {
        $this->attributes['email_verified_at'] = $value instanceof Carbon
            ? $value
            : Carbon::parse($value);
    }
}
