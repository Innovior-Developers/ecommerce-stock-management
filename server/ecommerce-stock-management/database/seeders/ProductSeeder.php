<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();

        $products = [
            [
                'name' => 'iPhone 15 Pro',
                'description' => 'Latest iPhone with advanced features',
                'price' => 999.99,
                'sku' => 'IPH15PRO001',
                'category' => $categories->where('slug', 'electronics')->first()?->name ?? 'Electronics',
                'stock_quantity' => 50,
                'status' => 'active',
            ],
            [
                'name' => 'Samsung Galaxy S24',
                'description' => 'Premium Android smartphone',
                'price' => 899.99,
                'sku' => 'SGS24001',
                'category' => $categories->where('slug', 'electronics')->first()?->name ?? 'Electronics',
                'stock_quantity' => 75,
                'status' => 'active',
            ],
            [
                'name' => 'MacBook Pro M3',
                'description' => 'Professional laptop with M3 chip',
                'price' => 1999.99,
                'sku' => 'MBP2024001',
                'category' => $categories->where('slug', 'electronics')->first()?->name ?? 'Electronics',
                'stock_quantity' => 25,
                'status' => 'active',
            ],
            [
                'name' => 'Nike Air Force 1',
                'description' => 'Classic white sneakers',
                'price' => 89.99,
                'sku' => 'NAF1WHITE',
                'category' => $categories->where('slug', 'clothing')->first()?->name ?? 'Clothing',
                'stock_quantity' => 100,
                'status' => 'active',
            ],
            [
                'name' => 'The Great Gatsby',
                'description' => 'Classic American novel',
                'price' => 14.99,
                'sku' => 'BOOK001',
                'category' => $categories->where('slug', 'books')->first()?->name ?? 'Books',
                'stock_quantity' => 200,
                'status' => 'active',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}