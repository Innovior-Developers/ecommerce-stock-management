<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class OAuthClient extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'oauth_clients';

    protected $fillable = [
        'user_id',
        'name',
        'secret',
        'provider',
        'redirect',
        'personal_access_client',
        'password_client',
        'revoked',
    ];

    protected $casts = [
        'personal_access_client' => 'boolean',
        'password_client' => 'boolean',
        'revoked' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
