<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ✅ Register payment gateway services
        $this->app->bind(
            \App\Services\PaymentGateway\PaymentGatewayInterface::class,
            function ($app) {
                $gateway = config('payment.default_gateway', 'stripe');
                
                return match($gateway) {
                    'stripe' => new \App\Services\PaymentGateway\StripeService(),
                    'paypal' => new \App\Services\PaymentGateway\PayPalService(),
                    'payhere' => new \App\Services\PaymentGateway\PayHereService(),
                    default => new \App\Services\PaymentGateway\StripeService(),
                };
            }
        );

        // ✅ Register individual services
        $this->app->singleton(\App\Services\PaymentGateway\StripeService::class);
        $this->app->singleton(\App\Services\PaymentGateway\PayPalService::class);
        $this->app->singleton(\App\Services\PaymentGateway\PayHereService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Configure custom rate limiters
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many login attempts. Please try again in 1 minute.',
                    ], 429, $headers);
                });
        });

        // API endpoints: 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}