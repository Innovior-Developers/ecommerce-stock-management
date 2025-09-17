<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
// Import the correct Authenticatable contract
use Illuminate\Contracts\Auth\Authenticatable;

class PersonalAccessToken extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'personal_access_tokens';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = '_id';

    /**
     * The "type" of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

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
    public static function findToken($token)
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
        return in_array('*', $this->abilities ?? []) ||
               in_array($ability, $this->abilities ?? []);
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

    /**
     * Get all of the token's abilities.
     */
    public function getAbilities()
    {
        return $this->abilities ?? ['*'];
    }

    /**
     * Determine if the current token has the given ability.
     */
    public function tokenCan(string $ability): bool
    {
        return $this->can($ability);
    }

    /**
     * Create a transient access token that is not persisted to storage.
     */
    public static function createTransientToken(Authenticatable $tokenable, string $name, array $abilities = ['*'])
    {
        $plainTextToken = \Illuminate\Support\Str::random(40);

        // Handle MongoDB _id field properly
        $tokenableId = $tokenable->_id ?? $tokenable->id ?? null;

        return new static([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
            'tokenable_type' => get_class($tokenable),
            'tokenable_id' => (string) $tokenableId,
        ]);
    }

    /**
     * Determine if the token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Update the "last used at" timestamp of the token.
     */
    public function markAsUsed(): void
    {
        if ($this->last_used_at === null || $this->last_used_at->isBefore(now()->subMinute())) {
            $this->forceFill(['last_used_at' => now()])->save();
        }
    }

    /**
     * Get the key name for route model binding.
     */
    public function getRouteKeyName()
    {
        return '_id';
    }

    /**
     * Get the primary key for the model.
     */
    public function getKeyName()
    {
        return '_id';
    }
}
