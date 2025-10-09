<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create customer user
        $user = User::updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Test Customer',
                'password' => Hash::make('customer123'), // ✅ Manually hash
                'role' => 'customer',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Create customer profile
        Customer::updateOrCreate(
            ['user_id' => $user->_id],
            [
                'first_name' => 'Test',
                'last_name' => 'Customer',
                'phone' => '+1234567890',
                'marketing_consent' => false,
            ]
        );

        $this->command->info('✅ Customer user created: ' . $user->email . ' / customer123');
    }
}
