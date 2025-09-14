<?php

namespace App\Guards;

use Laravel\Sanctum\Guard;
use App\Models\PersonalAccessToken;

class SanctumGuard extends Guard
{
    /**
     * Find the token instance matching the given token.
     */
    protected function findAccessToken($token)
    {
        if (strpos($token, '|') === false) {
            return PersonalAccessToken::where('token', hash('sha256', $token))->first();
        }

        [$id, $token] = explode('|', $token, 2);

        if ($instance = PersonalAccessToken::find($id)) {
            return hash_equals($instance->token, hash('sha256', $token)) ? $instance : null;
        }

        return null;
    }
}
