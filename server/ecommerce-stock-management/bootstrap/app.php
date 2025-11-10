<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ✅ GLOBAL middleware (runs on ALL requests)
        $middleware->use([
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        // ✅ API middleware (for /api/* routes ONLY)
        $middleware->api(prepend: [
            \App\Http\Middleware\SanitizeInput::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // ✅ WEB middleware (for web routes)
        $middleware->web(append: [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // // ✅ FIX: Use $_ENV instead of config()
        // if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production') {
        //     $middleware->web(append: [
        //         \App\Http\Middleware\ForceHttps::class,
        //     ]);

        //     $middleware->api(append: [
        //         \App\Http\Middleware\ForceHttps::class,
        //     ]);
        // }

        // ✅ Middleware aliases
        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JWTMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'force.https' => \App\Http\Middleware\ForceHttps::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();