<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OAuth Providers
    |--------------------------------------------------------------------------
    |
    | Configure OAuth providers for social login
    |
    */
    'providers' => [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('APP_URL') . '/auth/google/callback',
            'scopes' => ['openid', 'profile', 'email'],
        ],

        'github' => [
            'client_id' => env('GITHUB_CLIENT_ID'),
            'client_secret' => env('GITHUB_CLIENT_SECRET'),
            'redirect' => env('APP_URL') . '/auth/github/callback',
            'scopes' => ['read:user', 'user:email'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Configuration
    |--------------------------------------------------------------------------
    */
    'tokens' => [
        'access_token_lifetime' => (int) env('OAUTH_ACCESS_TOKEN_LIFETIME', 3600), // Cast to int
        'refresh_token_lifetime' => (int) env('OAUTH_REFRESH_TOKEN_LIFETIME', 86400 * 30), // Cast to int
    ],
];
