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
                'description' => 'Latest iPhone with advanced features including titanium design, A17 Pro chip, and pro camera system',
                'price' => 999.99,
                // SKU will be auto-generated
                'category' => $categories->where('slug', 'electronics')->first()?->name ?? 'Electronics',
                'stock_quantity' => 50,
                'status' => 'active',
                'weight' => 0.221,
                'meta_title' => 'iPhone 15 Pro - Advanced Smartphone',
                'meta_description' => 'Experience the power of iPhone 15 Pro with titanium design and A17 Pro chip',
                'images' => [], // Will be empty initially, can be added later via admin
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'description' => 'Premium Android smartphone with S Pen, advanced camera system, and AI features',
                'price' => 1199.99,
                'category' => $categories->where('slug', 'electronics')->first()?->name ?? 'Electronics',
                'stock_quantity' => 75,
                'status' => 'active',
                'weight' => 0.232,
                'meta_title' => 'Galaxy S24 Ultra - Premium Android',
                'meta_description' => 'Discover Samsung Galaxy S24 Ultra with S Pen and advanced AI capabilities',
                'images' => [],
            ],
            [
                'name' => 'MacBook Pro M3',
                'description' => 'Professional laptop with M3 chip, stunning Liquid Retina display, and all-day battery life',
                'price' => 1999.99,
                'category' => $categories->where('slug', 'electronics')->first()?->name ?? 'Electronics',
                'stock_quantity' => 25,
                'status' => 'active',
                'weight' => 1.55,
                'dimensions' => ['length' => 31.26, 'width' => 22.12, 'height' => 1.55],
                'meta_title' => 'MacBook Pro M3 - Professional Laptop',
                'meta_description' => 'Unleash your creativity with MacBook Pro M3 featuring advanced performance',
                'images' => [],
            ],
            [
                'name' => 'Nike Air Force 1',
                'description' => 'Classic white sneakers with premium leather upper and comfortable sole',
                'price' => 89.99,
                'category' => $categories->where('slug', 'clothing')->first()?->name ?? 'Clothing',
                'stock_quantity' => 100,
                'status' => 'active',
                'weight' => 0.8,
                'meta_title' => 'Nike Air Force 1 - Classic Sneakers',
                'meta_description' => 'Step out in style with Nike Air Force 1 classic white sneakers',
                'images' => [],
            ],
            [
                'name' => 'The Great Gatsby',
                'description' => 'Classic American novel by F. Scott Fitzgerald, a masterpiece of modern literature',
                'price' => 14.99,
                'category' => $categories->where('slug', 'books')->first()?->name ?? 'Books',
                'stock_quantity' => 200,
                'status' => 'active',
                'weight' => 0.3,
                'meta_title' => 'The Great Gatsby - Classic Novel',
                'meta_description' => 'Read the timeless classic The Great Gatsby by F. Scott Fitzgerald',
                'images' => [],
            ],
            [
                'name' => 'Sony WH-1000XM5 Headphones',
                'description' => 'Industry-leading noise canceling wireless headphones with superior sound quality',
                'price' => 349.99,
                'category' => $categories->where('slug', 'electronics')->first()?->name ?? 'Electronics',
                'stock_quantity' => 60,
                'status' => 'active',
                'weight' => 0.25,
                'meta_title' => 'Sony WH-1000XM5 - Premium Headphones',
                'meta_description' => 'Experience superior sound with Sony WH-1000XM5 noise canceling headphones',
                'images' => [],
            ],
            [
                'name' => 'iPad Pro 12.9"',
                'description' => 'Powerful tablet with M2 chip, Liquid Retina XDR display, and Apple Pencil support',
                'price' => 1099.99,
                'category' => $categories->where('slug', 'electronics')->first()?->name ?? 'Electronics',
                'stock_quantity' => 40,
                'status' => 'active',
                'weight' => 0.682,
                'dimensions' => ['length' => 28.06, 'width' => 21.49, 'height' => 0.64],
                'meta_title' => 'iPad Pro 12.9 - Professional Tablet',
                'meta_description' => 'Unleash creativity with iPad Pro 12.9 featuring M2 chip and stunning display',
                'images' => [],
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::create($productData);

            // Output the generated SKU for reference
            echo "✅ Product '{$product->name}' created with SKU: {$product->sku}\n";
        }

        echo "\n🎉 Product seeder completed! All products have auto-generated SKUs.\n";
    }
}