<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class DebugController extends Controller
{
    /**
     * ⚠️ DEVELOPMENT ONLY - DISABLE IN PRODUCTION
     * Check .env: APP_ENV=production should disable this
     */
    public function authInfo(Request $request)
    {
        // ✅ Disable in production
        if (config('app.env') === 'production') {
            return response()->json([
                'success' => false,
                'message' => 'Debug endpoints are disabled in production'
            ], 403);
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
            $payload = JWTAuth::parseToken()->getPayload();

            // ✅ SECURE: Don't expose sensitive data
            return response()->json([
                'success' => true,
                'authenticated' => true,
                'user' => [
                    // ❌ REMOVED: 'id'
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                ],
                'token_info' => [
                    'expires_at' => date('Y-m-d H:i:s', $payload->get('exp')),
                    'time_to_expire' => max(0, $payload->get('exp') - time()) . ' seconds',
                    'is_expired' => $payload->get('exp') < time(),
                ],
                'request_info' => [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ],
                // ❌ REMOVED: token_payload, token_claims (security risk)
                'timestamp' => now()
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Token has expired',
                'error_code' => 'TOKEN_EXPIRED',
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Token is invalid',
                'error_code' => 'TOKEN_INVALID',
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication error',
            ], 401);
        }
    }

    /**
     * ⚠️ DEVELOPMENT ONLY - Test token refresh
     */
    public function testRefresh(Request $request)
    {
        // ✅ Disable in production
        if (config('app.env') === 'production') {
            return response()->json([
                'success' => false,
                'message' => 'Debug endpoints are disabled in production'
            ], 403);
        }

        try {
            $oldToken = JWTAuth::getToken();
            $newToken = JWTAuth::refresh($oldToken);
            $user = JWTAuth::setToken($newToken)->toUser();

            // ✅ SECURE: Don't expose token details
            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                // ❌ REMOVED: old_token, new_token (security risk)
                'user' => [
                    // ❌ REMOVED: 'id'
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Token refresh failed',
            ], 401);
        }
    }
}
