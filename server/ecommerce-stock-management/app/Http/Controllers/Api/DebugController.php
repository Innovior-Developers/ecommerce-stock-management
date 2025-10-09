<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class DebugController extends Controller
{
    /**
     * Debug authentication information
     */
    public function authInfo(Request $request)
    {
        try {
            // Get user using JWTAuth
            $user = JWTAuth::parseToken()->authenticate();

            // Get token payload
            $payload = JWTAuth::parseToken()->getPayload();

            return response()->json([
                'success' => true,
                'authenticated' => true,
                'user' => [
                    'id' => $user->_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'avatar' => $user->avatar,
                ],
                'token_payload' => $payload->toArray(),
                'token_claims' => [
                    'iss' => $payload->get('iss'),      // Issuer
                    'iat' => $payload->get('iat'),      // Issued at
                    'exp' => $payload->get('exp'),      // Expires at
                    'nbf' => $payload->get('nbf'),      // Not before
                    'sub' => $payload->get('sub'),      // Subject (user ID)
                    'jti' => $payload->get('jti'),      // JWT ID
                    'role' => $payload->get('role'),    // Custom claim
                    'email' => $payload->get('email'),  // Custom claim
                    'name' => $payload->get('name'),    // Custom claim
                    'status' => $payload->get('status'), // Custom claim
                ],
                'token_info' => [
                    'expires_at' => date('Y-m-d H:i:s', $payload->get('exp')),
                    'issued_at' => date('Y-m-d H:i:s', $payload->get('iat')),
                    'time_to_expire' => max(0, $payload->get('exp') - time()) . ' seconds',
                    'is_expired' => $payload->get('exp') < time(),
                    'ttl_minutes' => config('jwt.ttl'),
                    'refresh_ttl_minutes' => config('jwt.refresh_ttl'),
                ],
                'request_info' => [
                    'authorization_header' => $request->header('Authorization'),
                    'user_agent' => $request->header('User-Agent'),
                    'ip_address' => $request->ip(),
                    'method' => $request->method(),
                    'url' => $request->url(),
                ],
                'guard' => 'api (JWT)',
                'timestamp' => now()
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Token has expired',
                'error_code' => 'TOKEN_EXPIRED',
                'authenticated' => false,
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Token is invalid',
                'error_code' => 'TOKEN_INVALID',
                'authenticated' => false,
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Token is required',
                'error_code' => 'TOKEN_ABSENT',
                'authenticated' => false,
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'authenticated' => false,
            ], 500);
        }
    }

    /**
     * Test token refresh
     */
    public function testRefresh(Request $request)
    {
        try {
            $oldToken = JWTAuth::getToken();
            $newToken = JWTAuth::refresh($oldToken);
            $user = JWTAuth::setToken($newToken)->toUser();

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'old_token' => (string) $oldToken,
                'new_token' => $newToken,
                'user' => [
                    'id' => $user->_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 401);
        }
    }
}
