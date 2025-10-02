<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ValidateApiToken
{
    /**
     * Handle an incoming request - Prevent token reuse after logout
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if ($token) {
            // Check if token is blacklisted
            $tokenHash = hash('sha256', $token);

            if (Cache::has("blacklisted_token_{$tokenHash}")) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token has been revoked',
                    'error_code' => 'TOKEN_BLACKLISTED'
                ], 401);
            }
        }

        return $next($request);
    }
}
