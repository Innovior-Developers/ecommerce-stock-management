<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'description' => 'Electronic devices and accessories',
                'slug' => 'electronics',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'name' => 'Clothing',
                'description' => 'Fashion and apparel',
                'slug' => 'clothing',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'name' => 'Books',
                'description' => 'Books and literature',
                'slug' => 'books',
                'status' => 'active',
                'sort_order' => 3,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
