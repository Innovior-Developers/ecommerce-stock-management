<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Laravel\Passport\RouteRegistrar;
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
    // public function boot(): void
    // {
    //     // Configure Passport to use custom MongoDB models instead of default Eloquent models
    //     Passport::useClientModel(OAuthClient::class);
    //     Passport::useTokenModel(OAuthAccessToken::class);
    //     Passport::useRefreshTokenModel(OAuthRefreshToken::class);

    //     // Register OAuth2 routes for token management
    //     $this->registerRoutes();

    //     // Set token expiration times
    //     Passport::tokensExpireIn(now()->addHours(1));           // Access tokens expire in 1 hour
    //     Passport::refreshTokensExpireIn(now()->addDays(30));    // Refresh tokens expire in 30 days
    //     Passport::personalAccessTokensExpireIn(now()->addMonths(6)); // Personal access tokens expire in 6 months
    // }

    /**
     * Register Passport OAuth2 routes
     * Uncomment and customize as needed for specific route requirements
     */
    // protected function registerRoutes()
    // {
    //     Passport::routes(function (RouteRegistrar $router) {
    //         $router->forAccessTokens();        // Routes for access token management
    //         $router->forPersonalAccessTokens(); // Routes for personal access tokens
    //         $router->forTransientTokens();      // Routes for transient tokens
    //     });
    // }
}
