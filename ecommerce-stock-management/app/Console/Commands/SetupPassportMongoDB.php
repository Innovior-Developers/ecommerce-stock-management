<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OAuthClient;
use Illuminate\Support\Str;

class SetupPassportMongoDB extends Command
{
    protected $signature = 'passport:setup-mongodb';
    protected $description = 'Setup Passport OAuth clients in MongoDB';

    public function handle()
    {
        $this->info('Setting up Passport OAuth clients in MongoDB...');

        // Create Personal Access Client
        $personalAccessClient = OAuthClient::create([
            'user_id' => null,
            'name' => config('app.name') . ' Personal Access Client',
            'secret' => Str::random(40),
            'provider' => null,
            'redirect' => 'http://localhost',
            'personal_access_client' => true,
            'password_client' => false,
            'revoked' => false,
        ]);

        // Create Password Grant Client
        $passwordClient = OAuthClient::create([
            'user_id' => null,
            'name' => config('app.name') . ' Password Grant Client',
            'secret' => Str::random(40),
            'provider' => null,
            'redirect' => 'http://localhost',
            'personal_access_client' => false,
            'password_client' => true,
            'revoked' => false,
        ]);

        $this->info('Personal Access Client ID: ' . $personalAccessClient->_id);
        $this->info('Password Grant Client ID: ' . $passwordClient->_id);
        $this->info('Password Grant Client Secret: ' . $passwordClient->secret);

        $this->info('OAuth clients created successfully!');
        $this->info('Add these to your .env file:');
        $this->info('PASSPORT_PERSONAL_ACCESS_CLIENT_ID=' . $personalAccessClient->_id);
        $this->info('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=' . $personalAccessClient->secret);
        $this->info('PASSPORT_PASSWORD_GRANT_CLIENT_ID=' . $passwordClient->_id);
        $this->info('PASSPORT_PASSWORD_GRANT_CLIENT_SECRET=' . $passwordClient->secret);

        return 0;
    }
}