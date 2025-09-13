<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Laravel\Passport\Passport;
use App\Models\OAuthClient;
use App\Models\OAuthAccessToken;
use App\Models\OAuthRefreshToken;

/**
 * PassportServiceProvider handles Laravel Passport OAuth2 server configuration
 * Configures MongoDB models and token lifetimes for OAuth authentication
 */
class PassportServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     * Called during the service container registration phase
     */
    public function register(): void
    {
        // No services to register in this provider
    }

    /**
     * Bootstrap services.
     * Called after all other service providers have been registered
     */
    public function boot(): void
    {
        // Use MongoDB-backed models
        Passport::useClientModel(OAuthClient::class);
        Passport::useTokenModel(OAuthAccessToken::class);
        Passport::useRefreshTokenModel(OAuthRefreshToken::class);

        // Do NOT call Passport::routes() on Passport v12

        // Optional: define scopes
        Passport::tokensCan([
            'admin' => 'Full admin access',
            'customer' => 'Customer actions',
        ]);

        // Token lifetimes (env overrides)
        Passport::tokensExpireIn(now()->addSeconds((int) env('PASSPORT_ACCESS_TOKEN_TTL', 3600)));
        Passport::refreshTokensExpireIn(now()->addSeconds((int) env('PASSPORT_REFRESH_TOKEN_TTL', 60 * 60 * 24 * 30)));
        Passport::personalAccessTokensExpireIn(now()->addSeconds((int) env('PASSPORT_PERSONAL_TOKEN_TTL', 60 * 60 * 24 * 30 * 6)));

        // Ensure keys exist in local/dev
        if (App::environment(['local', 'development'])) {
            $priv = storage_path('oauth-private.key');
            $pub  = storage_path('oauth-public.key');
            if (!File::exists($priv) || !File::exists($pub)) {
                try {
                    Artisan::call('passport:keys', ['--force' => true]);
                } catch (\Throwable $e) {
                    report($e); // non-blocking
                }
            }
        }
    }
}
