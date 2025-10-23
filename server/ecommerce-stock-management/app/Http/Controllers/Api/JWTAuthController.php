<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Services\QuerySanitizer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // ✅ Add this
use App\Models\JwtBlacklist;
use Illuminate\Validation\Rules\Password;
use MongoDB\BSON\ObjectId; // ✅ Add this line

class JWTAuthController extends Controller
{
    /**
     * Admin Login
     */
    public function adminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $email = QuerySanitizer::sanitize($credentials['email']);

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active.',
            ], 403);
        }

        $token = JWTAuth::fromUser($user);

        return $this->respondWithToken($token, $user, 'Admin login successful');
    }

    /**
     * Customer Login
     */
    public function customerLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $email = QuerySanitizer::sanitize($credentials['email']);

        $user = User::where('email', $email)->where('role', 'customer')->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active.',
            ], 403);
        }

        $token = JWTAuth::fromUser($user);

        return $this->respondWithToken($token, $user, 'Login successful');
    }

    /**
     * Customer Registration with Strong Password Validation
     */
    public function customerRegister(Request $request)
    {
        \DB::beginTransaction(); // ✅ Use database transaction for data consistency

        try {
            // ✅ Validate inputs
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => [
                    'required',
                    'string',
                    'confirmed',
                    Password::min(12)
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised(),
                ],
                'password_confirmation' => 'required|string',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
            ], [
                'password.min' => 'Password must be at least 12 characters long.',
                'password.mixed' => 'Password must contain both uppercase and lowercase letters.',
                'password.numbers' => 'Password must contain at least one number.',
                'password.symbols' => 'Password must contain at least one special character.',
                'password.uncompromised' => 'This password has been found in data breaches. Please choose a different password.',
            ]);

            // ✅ Sanitize inputs
            $validated['name'] = QuerySanitizer::sanitize($validated['name']);
            $validated['email'] = QuerySanitizer::sanitize($validated['email']);

            if (isset($validated['first_name'])) {
                $validated['first_name'] = QuerySanitizer::sanitize($validated['first_name']);
            }
            if (isset($validated['last_name'])) {
                $validated['last_name'] = QuerySanitizer::sanitize($validated['last_name']);
            }
            if (isset($validated['phone'])) {
                $validated['phone'] = QuerySanitizer::sanitize($validated['phone']);
            }

            // ✅ STEP 1: Create customer profile using save() (forces _id generation)
            $customer = new Customer();
            $customer->first_name = $validated['first_name'] ?? '';
            $customer->last_name = $validated['last_name'] ?? '';
            $customer->phone = $validated['phone'] ?? '';
            $customer->marketing_consent = false;

            // ✅ CRITICAL: Save and verify _id was generated
            if (!$customer->save()) {
                throw new \Exception('Failed to create customer profile');
            }

            // ✅ Force refresh to ensure _id is available
            $customer->refresh();

            // ✅ Verify _id exists and is valid
            if (!$customer->_id || !($customer->_id instanceof \MongoDB\BSON\ObjectId)) {
                throw new \Exception('Customer _id not generated correctly');
            }

            $customerId = (string) $customer->_id;

            Log::info('✅ Customer created with _id', [
                'customer_id' => $customerId,
                'customer_id_type' => get_class($customer->_id),
                'first_name' => $customer->first_name,
            ]);

            // ✅ STEP 2: Create user with customer_id
            $user = new User();
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->password = Hash::make($validated['password']);
            $user->role = 'customer';
            $user->customer_id = $customerId; // ✅ String format
            $user->status = 'active';
            $user->email_verified_at = now();

            if (!$user->save()) {
                throw new \Exception('Failed to create user');
            }

            // ✅ Force refresh user
            $user->refresh();

            Log::info('✅ User created', [
                'user_id' => (string) $user->_id,
                'customer_id_in_user' => $user->customer_id,
            ]);

            // ✅ STEP 3: Final verification
            $userCheck = User::where('_id', $user->_id)->first();

            if (!$userCheck) {
                throw new \Exception('User verification failed');
            }

            if (empty($userCheck->customer_id)) {
                Log::error('❌ CRITICAL: customer_id empty after save', [
                    'user_id' => (string) $userCheck->_id,
                    'customer_id_value' => $userCheck->customer_id,
                ]);

                // ✅ Last resort: Force raw update
                \DB::connection('mongodb')
                    ->collection('users')
                    ->where('_id', $userCheck->_id)
                    ->update(['customer_id' => $customerId]);

                $userCheck = User::where('_id', $userCheck->_id)->first();

                Log::warning('⚠️ Force updated customer_id', [
                    'customer_id_now' => $userCheck->customer_id,
                ]);
            }

            // ✅ Commit transaction
            \DB::commit();

            // ✅ Generate JWT token
            $token = JWTAuth::fromUser($userCheck);

            Log::info('✅ Registration successful', [
                'user_id' => (string) $userCheck->_id,
                'customer_id' => $customerId,
                'customer_id_in_user' => $userCheck->customer_id,
            ]);

            return $this->respondWithToken($token, $userCheck, 'Registration successful', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \DB::rollBack();

            Log::error('❌ Registration validation failed', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \DB::rollBack();

            Log::error('❌ Registration error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get the authenticated User
     * ✅ CHANGED: Renamed from me() to user() to match route
     */
    public function user()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'avatar' => $user->avatar,
                ],
            ]);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired',
                'error_code' => 'TOKEN_EXPIRED'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid',
                'error_code' => 'TOKEN_INVALID'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token is required',
                'error_code' => 'TOKEN_ABSENT'
            ], 401);
        }
    }

    /**
     * Logout User (Invalidate token)
     */
    public function logout()
    {
        try {
            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'No token provided'
                ], 400);
            }

            // Get user before invalidating
            $user = JWTAuth::parseToken()->authenticate();

            // Add to blacklist BEFORE invalidating
            JwtBlacklist::add(
                $token->get(),
                config('jwt.ttl'),
                $user ? (string) $user->_id : null,
                'user_logout'
            );

            // Invalidate the token
            JWTAuth::invalidate($token);

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh JWT token
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60
            ]);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired and cannot be refreshed',
                'error_code' => 'TOKEN_EXPIRED'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not refresh token',
                'error_code' => 'TOKEN_REFRESH_FAILED'
            ], 500);
        }
    }

    /**
     * Update User Password
     */
    public function updatePassword(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $validated = $request->validate([
                'current_password' => 'required|string',
                'password' => [
                    'required',
                    'string',
                    'confirmed',
                    'different:current_password',     // ✅ New password must be different
                    Password::min(12)
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised(),
                ],
                'password_confirmation' => 'required|string',
            ], [
                'password.min' => 'Password must be at least 12 characters long.',
                'password.mixed' => 'Password must contain both uppercase and lowercase letters.',
                'password.numbers' => 'Password must contain at least one number.',
                'password.symbols' => 'Password must contain at least one special character.',
                'password.uncompromised' => 'This password has been found in data breaches. Please choose a different password.',
                'password.different' => 'New password must be different from current password.',
            ]);

            // Verify current password
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                    'errors' => [
                        'current_password' => ['The current password is incorrect.']
                    ]
                ], 422);
            }

            // Update password
            User::where('_id', $user->_id)->update([
                'password' => Hash::make($validated['password']),
            ]);

            // ✅ Optional: Invalidate all existing tokens and force re-login
            // This is a security best practice after password change
            $token = JWTAuth::getToken();
            if ($token) {
                JwtBlacklist::add(
                    $token->get(),
                    config('jwt.ttl'),
                    (string) $user->_id,
                    'password_change'
                );
            }

            // Generate new token
            $newToken = JWTAuth::fromUser($user);

            Log::info('Password updated successfully', ['user_id' => $user->_id]);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully. Please login with your new password.',
                'token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating password: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update password'
            ], 500);
        }
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken($token, $user, $message, $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'user' => [
                'id' => (string) $user->_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'avatar' => $user->avatar,
                'customer_id' => $user->customer_id ? (string) $user->customer_id : null,
            ],
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60
        ], $statusCode);
    }
}