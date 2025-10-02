<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth('api')->user();

        if (!$user || $user->role !== 'admin') {
            Log::warning('Unauthorized admin access attempt', [
                'user_id' => $user?->public_id ?? 'guest',
                'user_role' => $user?->role ?? 'none',
                'ip' => $request->ip(),
                'endpoint' => $request->path(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
                'error_code' => 'ADMIN_ACCESS_REQUIRED'
            ], 403);
        }

        return $next($request);
    }
}
