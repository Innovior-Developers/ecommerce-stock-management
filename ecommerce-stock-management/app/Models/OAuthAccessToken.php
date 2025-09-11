<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class OAuthAccessToken extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'oauth_access_tokens';

    protected $fillable = [
        'id',
        'user_id',
        'client_id',
        'name',
        'scopes',
        'revoked',
        'expires_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'revoked' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
