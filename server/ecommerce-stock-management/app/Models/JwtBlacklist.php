<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class JwtBlacklist extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'jwt_blacklist';

    protected $fillable = ['token_hash', 'expires_at', 'user_id', 'reason'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Check if a token is blacklisted
     */
    public static function isBlacklisted(string $token): bool
    {
        $hash = hash('sha256', $token);

        return self::where('token_hash', $hash)
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Add a token to the blacklist
     */
    public static function add(string $token, int $ttlMinutes, ?string $userId = null, string $reason = 'logout'): void
    {
        self::create([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes($ttlMinutes),
            'user_id' => $userId,
            'reason' => $reason,
        ]);
    }

    /**
     * Clean up expired tokens
     */
    public static function cleanup(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }
}
