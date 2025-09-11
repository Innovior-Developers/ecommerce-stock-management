<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register the OAuth setup command
Artisan::command('passport:setup-mongodb', function () {
    $this->call(\App\Console\Commands\SetupPassportMongoDB::class);
})->purpose('Setup Passport OAuth clients in MongoDB');
