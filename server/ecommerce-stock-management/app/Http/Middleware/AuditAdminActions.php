<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditAdminActions
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->role === 'admin') {
            $logData = [
                'admin_id' => $user->public_id ?? $user->getHashedIdAttribute(),
                'admin_name' => $user->name,
                'action' => $request->method(),
                'endpoint' => $request->path(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toIso8601String(),
                'payload' => $request->except(['password', 'password_confirmation', 'token', 'current_password']),
            ];

            // Log to separate admin channel
            Log::channel('admin')->info('Admin Action', $logData);

            // For critical actions, log to database as well
            if (in_array($request->method(), ['DELETE', 'PUT', 'PATCH'])) {
                Log::channel('admin')->warning('Critical Admin Action', array_merge($logData, [
                    'severity' => 'high',
                    'requires_review' => true
                ]));
            }
        }

        return $next($request);
    }
}
