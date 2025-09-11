<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use App\Models\OAuthClient;
use App\Models\OAuthAccessToken;
use App\Models\OAuthRefreshToken;

class PassportServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Use MongoDB models for OAuth
        Passport::useClientModel(OAuthClient::class);
        Passport::useTokenModel(OAuthAccessToken::class);
        Passport::useRefreshTokenModel(OAuthRefreshToken::class);

        // Set token expiration times from config
        Passport::tokensExpireIn(now()->addSeconds(config('oauth.tokens.access_token_lifetime', 3600)));
        Passport::refreshTokensExpireIn(now()->addSeconds(config('oauth.tokens.refresh_token_lifetime', 2592000)));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }
}
