<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * Redirects HTTP requests to HTTPS in production
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ✅ Only enforce HTTPS in production
        if (!$request->secure() && app()->environment('production')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        // ✅ Set HSTS header for production (tells browsers to always use HTTPS)
        $response = $next($request);

        if (app()->environment('production') && $response instanceof \Illuminate\Http\Response) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }
}
