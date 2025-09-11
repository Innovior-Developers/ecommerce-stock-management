<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Create test user
        User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Create sample products
        $products = [
            ['name' => 'Laptop', 'price' => 999.99, 'sku' => 'LAP001', 'category' => 'Electronics', 'stock_quantity' => 50],
            ['name' => 'Mouse', 'price' => 25.99, 'sku' => 'MOU001', 'category' => 'Electronics', 'stock_quantity' => 100],
            ['name' => 'Keyboard', 'price' => 79.99, 'sku' => 'KEY001', 'category' => 'Electronics', 'stock_quantity' => 75],
        ];

        foreach ($products as $productData) {
            Product::firstOrCreate(
                ['sku' => $productData['sku']],
                array_merge($productData, [
                    'description' => 'Sample product for testing',
                    'status' => 'active'
                ])
            );
        }
    }
}
