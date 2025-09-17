<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            InventorySeeder::class,
        ]);

        // // Create test user (using direct creation instead of factory)
        // User::firstOrCreate(
        //     ['email' => 'test@example.com'],
        //     [
        //         'name' => 'Test User',
        //         'password' => 'password123', // Will be hashed automatically
        //         'role' => 'user',
        //         'status' => 'active',
        //         'email_verified_at' => now(),
        //     ]
        // );

        // // Create admin user
        // User::firstOrCreate(
        //     ['email' => 'admin@example.com'],
        //     [
        //         'name' => 'Admin User',
        //         'password' => 'password123', // Will be hashed automatically
        //         'role' => 'admin',
        //         'status' => 'active',
        //         'email_verified_at' => now(),
        //     ]
        // );

        $this->command->info('✅ Users created successfully!');
    }
}