<?php

namespace App\Models;

use MongoDB\Laravel\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\NewAccessToken;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

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

    protected static function boot()
    {
        parent::boot();

        // Create indexes when model is first used
        static::created(function ($model) {
            try {
                $collection = $model->getCollection();

                // Create unique index for email
                $collection->createIndex(['email' => 1], ['unique' => true]);

                // Create index for role
                $collection->createIndex(['role' => 1]);

                // Create index for status
                $collection->createIndex(['status' => 1]);
            } catch (\Exception $e) {
                // Ignore if indexes already exist
            }
        });
    }

    /**
     * Get the access tokens for the user.
     */
    public function tokens()
    {
        return $this->morphMany(\App\Models\PersonalAccessToken::class, 'tokenable');
    }

    /**
     * Create a new personal access token for the user.
     */
    public function createToken(string $name, array $abilities = ['*'])
    {
        $plainTextToken = Str::random(40);

        $token = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
        ]);

        return new NewAccessToken($token, $token->_id.'|'.$plainTextToken);
    }

    // Relationships
    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id', '_id');
    }
}
