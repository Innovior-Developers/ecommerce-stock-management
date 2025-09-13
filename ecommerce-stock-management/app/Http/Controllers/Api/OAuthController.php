<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use App\Models\OAuthClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // Add this import
use Illuminate\Validation\ValidationException;

class OAuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        // Create user with customer role
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create customer profile
        $customer = Customer::create([
            'user_id' => (string) $user->_id,
            'first_name' => $validated['first_name'] ?? $validated['name'],
            'last_name' => $validated['last_name'] ?? '',
            'phone' => $validated['phone'] ?? null,
            'addresses' => [],
            'preferences' => [],
            'marketing_consent' => false,
        ]);

        // Get OAuth token
        $tokenResponse = $this->getOAuthToken($validated['email'], $validated['password']);

        return response()->json([
            'success' => true,
            'message' => 'Customer registered successfully',
            'user' => [
                'id' => $user->_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'customer' => $customer,
            'access_token' => $tokenResponse['access_token'],
            'refresh_token' => $tokenResponse['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokenResponse['expires_in'],
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',  // Add this line
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->where('role', 'customer')->first();

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

        // Get OAuth token
        $tokenResponse = $this->getOAuthToken($validated['email'], $validated['password']);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'customer' => $user->customer,
            'access_token' => $tokenResponse['access_token'],
            'refresh_token' => $tokenResponse['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokenResponse['expires_in'],
        ]);
    }

    public function refreshToken(Request $request)
    {
        $validated = $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $client = OAuthClient::where('password_client', true)->first();

        if (!$client) {
            throw ValidationException::withMessages([
                'refresh_token' => ['OAuth client not configured'],
            ]);
        }

        $response = Http::asForm()->post(config('app.url') . '/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $validated['refresh_token'],
            'client_id' => $client->_id,
            'client_secret' => $client->secret,
            'scope' => '',
        ]);

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Invalid refresh token'],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $response->json(),
        ]);
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
            'customer' => $user->customer,
        ]);
    }

    private function getOAuthToken($email, $password)
    {
        $clientId = env('PASSPORT_PASSWORD_GRANT_CLIENT_ID');
        $clientSecret = env('PASSPORT_PASSWORD_GRANT_CLIENT_SECRET');

        if (!$clientId || !$clientSecret) {
            throw new \Exception('OAuth client credentials not configured. Please check your .env file.');
        }

        $response = Http::asForm()->post(config('app.url') . '/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $email,
            'password' => $password,
            'scope' => '',
        ]);

        if ($response->failed()) {
            Log::error('OAuth token request failed for customer', [
                'status' => $response->status(),
                'response' => $response->body(),
                'email' => $email,
            ]);

            throw ValidationException::withMessages([
                'email' => ['Authentication failed'],
            ]);
        }

        return $response->json();
    }
}
