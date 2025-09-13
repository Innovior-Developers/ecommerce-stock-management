<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class OAuthClient extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'oauth_clients';

    // MongoDB PK settings
    protected $primaryKey = '_id';
    public $incrementing = false;
    protected $keyType = 'string';

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

    // Passport v12 expects these:

    // true if the client is first-party (has a user_id)
    public function firstParty(): bool
    {
        return !empty($this->user_id);
    }

    // true if the client keeps a secret
    public function confidential(): bool
    {
        return !empty($this->secret);
    }

    // grant handling (mirrors Passport\Client behavior)
    public function hasGrantType(string $grantType): bool
    {
        switch ($grantType) {
            case 'authorization_code':
                return !$this->firstParty();
            case 'personal_access':
                return $this->personal_access_client && $this->confidential();
            case 'password':
                return $this->password_client === true;
            case 'client_credentials':
                return $this->confidential();
            default:
                return false;
        }
    }
}
