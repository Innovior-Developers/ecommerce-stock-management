<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Model implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationships
    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id', '_id');
    }
}
