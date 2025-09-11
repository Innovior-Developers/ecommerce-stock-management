<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use MongoDB\Laravel\Relations\HasMany;

class User extends Model implements AuthenticatableContract
{
    use Authenticatable, Notifiable, HasApiTokens;

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

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function findForPassport($username)
    {
        return $this->where('email', $username)->first();
    }

    // Override for Passport compatibility with MongoDB
    public function getKey()
    {
        return (string) $this->_id;
    }

    public function getKeyName()
    {
        return '_id';
    }

    // Custom method to validate password for Passport
    public function validateForPassportPasswordGrant($password)
    {
        return app('hash')->check($password, $this->password);
    }
}
