<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JwtBlacklist;

class CleanupBlacklistedTokens extends Command
{
    protected $signature = 'jwt:cleanup-blacklist';
    protected $description = 'Remove expired tokens from blacklist';

    public function handle()
    {
        $count = JwtBlacklist::cleanup();

        $this->info("Cleaned up {$count} expired tokens from blacklist.");

        return 0;
    }
}
