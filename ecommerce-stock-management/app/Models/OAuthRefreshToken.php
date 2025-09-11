<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class OAuthRefreshToken extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'oauth_refresh_tokens';

    protected $fillable = [
        'id',
        'access_token_id',
        'revoked',
        'expires_at',
    ];

    protected $casts = [
        'revoked' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
