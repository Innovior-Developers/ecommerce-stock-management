<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Use our custom MongoDB PersonalAccessToken model
        Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);

        // Define custom gates for role-based access
        Gate::define('admin-access', function ($user) {
            return $user->role === 'admin';
        });

        Gate::define('customer-access', function ($user) {
            return $user->role === 'customer';
        });
    }
}
