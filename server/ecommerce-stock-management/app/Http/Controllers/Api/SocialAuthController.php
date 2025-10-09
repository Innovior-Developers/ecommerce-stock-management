<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function redirectToProvider($provider)
    {
        $validProviders = ['google', 'github', 'facebook'];

        if (!in_array($provider, $validProviders)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid provider',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'redirect_url' => Socialite::driver($provider)->redirect()->getTargetUrl(),
        ]);
    }

    public function handleProviderCallback($provider, Request $request)
    {
        try {
            $socialiteUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to authenticate with ' . $provider,
                'error' => $e->getMessage(),
            ], 400);
        }

        // Check if user already exists with this provider
        $user = User::where('provider', $provider)
                   ->where('provider_id', $socialiteUser->getId())
                   ->first();

        if (!$user) {
            // Check if user exists with same email
            $existingUser = User::where('email', $socialiteUser->getEmail())->first();

            if ($existingUser) {
                // Link the provider to existing user
                $existingUser->update([
                    'provider' => $provider,
                    'provider_id' => $socialiteUser->getId(),
                    'avatar' => $socialiteUser->getAvatar(),
                ]);
                $user = $existingUser;
            } else {
                // Create new user
                $user = User::create([
                    'name' => $socialiteUser->getName(),
                    'email' => $socialiteUser->getEmail(),
                    'password' => Hash::make(Str::random(24)), // Random password
                    'role' => 'customer',
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'provider' => $provider,
                    'provider_id' => $socialiteUser->getId(),
                    'avatar' => $socialiteUser->getAvatar(),
                ]);

                // Create customer profile
                Customer::create([
                    'user_id' => $user->_id,
                    'first_name' => $socialiteUser->getName(),
                    'last_name' => '',
                    'marketing_consent' => false,
                ]);
            }
        }

        // Revoke existing tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('social-login-token', ['customer'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Social login successful',
            'user' => [
                'id' => $user->_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar,
                'provider' => $user->provider,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
