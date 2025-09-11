<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OAuthClient;
use Illuminate\Support\Str;

class SetupOAuth extends Command
{
    protected $signature = 'oauth:setup';
    protected $description = 'Setup OAuth clients for MongoDB';

    public function handle()
    {
        $this->info('Setting up OAuth clients...');

        // Create Personal Access Client
        $personalClient = OAuthClient::create([
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

        $this->info('OAuth clients created successfully!');
        $this->info('Personal Access Client ID: ' . $personalClient->_id);
        $this->info('Password Grant Client ID: ' . $passwordClient->_id);
        
        return 0;
    }
}