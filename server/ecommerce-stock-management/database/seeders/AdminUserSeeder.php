<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use Carbon\Carbon;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => Carbon::now(),
                'provider' => null,
                'provider_id' => null,
                'avatar' => null,
            ]
        );

        // // Create customer user
        // $customerUser = User::updateOrCreate(
        //     ['email' => 'customer@example.com'],
        //     [
        //         'name' => 'Test Customer',
        //         'email' => 'customer@example.com',
        //         'password' => bcrypt('customer123'),
        //         'role' => 'customer',
        //         'status' => 'active',
        //         'email_verified_at' => Carbon::now(),
        //         'provider' => null,
        //         'provider_id' => null,
        //         'avatar' => null,
        //     ]
        // );

        // Create customer profile
        // Customer::updateOrCreate(
        //     ['user_id' => $customerUser->_id],
        //     [
        //         'user_id' => $customerUser->_id,
        //         'first_name' => 'Test',
        //         'last_name' => 'Customer',
        //         'phone' => '+1234567890',
        //         'marketing_consent' => false,
        //     ]
        // );

        echo "✅ Admin user created: admin@example.com / admin123\n";
        echo "✅ Customer user created: customer@example.com / customer123\n";
    }
}