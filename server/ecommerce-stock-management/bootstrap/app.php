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
        // ✅ API middleware (for /api/* routes)
        $middleware->api(prepend: [
            \App\Http\Middleware\SanitizeInput::class,      // ✅ Input sanitization
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,  // ✅ CORS handling
        ]);

        // ✅ Middleware aliases
        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JWTMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        // ❌ DO NOT add session, CSRF, or cookie middleware for API
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
