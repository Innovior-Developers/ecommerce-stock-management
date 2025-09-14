<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use our custom MongoDB-compatible PersonalAccessToken model
        Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);

        // For MongoDB, we don't need to ignore migrations since we handle them manually
        // Just ensure the model is properly configured above
    }
}
