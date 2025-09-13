<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->where('role', 'admin')->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
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

        // Get OAuth token for admin
        try {
            $tokenResponse = $this->getOAuthToken($validated['email'], $validated['password']);

            return response()->json([
                'success' => true,
                'message' => 'Admin login successful',
                'user' => [
                    'id' => $user->_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'access_token' => $tokenResponse['access_token'],
                'refresh_token' => $tokenResponse['refresh_token'],
                'token_type' => 'Bearer',
                'expires_in' => $tokenResponse['expires_in'],
            ]);
        } catch (\Exception $e) {
            Log::error('Admin OAuth token generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->_id,
            ]);

            throw ValidationException::withMessages([
                'email' => ['Authentication system error. Please try again.'],
            ]);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    private function getOAuthToken($email, $password)
    {
        // Use environment variables directly instead of querying database
        $clientId = env('PASSPORT_PASSWORD_GRANT_CLIENT_ID');
        $clientSecret = env('PASSPORT_PASSWORD_GRANT_CLIENT_SECRET');

        if (!$clientId || !$clientSecret) {
            throw new \Exception('OAuth client credentials not configured in .env file');
        }

        $response = Http::timeout(30)->asForm()->post(env('APP_URL') . '/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $email,
            'password' => $password,
            'scope' => '',
        ]);

        if ($response->failed()) {
            Log::error('OAuth token request failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'client_id' => $clientId,
                'url' => env('APP_URL') . '/oauth/token',
            ]);

            throw new \Exception('OAuth token generation failed: ' . $response->body());
        }

        return $response->json();
    }
}
