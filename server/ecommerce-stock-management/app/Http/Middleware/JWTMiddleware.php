<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Models\JWTBlacklist;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class JWTMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // ✅ Get token from header
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not provided',
                ], 401);
            }

            // ✅ Check if token is blacklisted
            $isBlacklisted = JWTBlacklist::where('token', $token)
                ->where('expires_at', '>', now())
                ->exists();

            if ($isBlacklisted) {
                Log::warning('Blacklisted token used', [
                    'token' => substr($token, 0, 20) . '...',
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Token has been revoked',
                ], 401);
            }

            // ✅ Authenticate user via JWT
            /** @var User|null $user */
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 401);
            }

            // ✅ Load customer relationship if user is a customer
            if ($user->role === 'customer') {
                $customerRelation = User::with('customer')->where('_id', $user->_id)->first();
                if ($customerRelation && $customerRelation->customer) {
                    $user->setRelation('customer', $customerRelation->customer);
                }
            }

            // ✅ Set authenticated user for the request
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired',
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid',
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token authentication failed',
            ], 401);
        }

        return $next($request);
    }
}
