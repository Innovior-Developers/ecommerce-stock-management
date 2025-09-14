<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class PersonalAccessToken extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'personal_access_tokens';

    protected $fillable = [
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
        'tokenable_type',
        'tokenable_id',
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * Get the tokenable model that the access token belongs to.
     */
    public function tokenable()
    {
        return $this->morphTo();
    }

    /**
     * Find the token instance matching the given token.
     */
    public function findToken($token)
    {
        if (strpos($token, '|') === false) {
            return static::where('token', hash('sha256', $token))->first();
        }

        [$id, $token] = explode('|', $token, 2);

        if ($instance = static::find($id)) {
            return hash_equals($instance->token, hash('sha256', $token)) ? $instance : null;
        }

        return null;
    }

    /**
     * Determine if the token has a given ability.
     */
    public function can($ability)
    {
        return in_array('*', $this->abilities) ||
               in_array($ability, $this->abilities);
    }

    /**
     * Determine if the token is missing a given ability.
     */
    public function cant($ability)
    {
        return ! $this->can($ability);
    }

    /**
     * Determine if the token has any of the given abilities.
     */
    public function canAny(array $abilities)
    {
        return collect($abilities)->contains(function ($ability) {
            return $this->can($ability);
        });
    }
}
