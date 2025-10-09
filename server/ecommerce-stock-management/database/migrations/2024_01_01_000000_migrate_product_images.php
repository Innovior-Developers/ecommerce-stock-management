<?php
// Create this file: database/migrations/2024_01_01_000000_migrate_product_images.php

use Illuminate\Database\Migrations\Migration;
use App\Models\Product;

return new class extends Migration
{
    public function up()
    {
        $products = Product::whereNotNull('image_url')->get();

        foreach ($products as $product) {
            if ($product->image_url && empty($product->images)) {
                $images = [[
                    'url' => $product->image_url,
                    'path' => null, // External URL, no S3 path
                    'filename' => basename($product->image_url),
                    'uploaded_at' => now(),
                    'is_primary' => true,
                ]];

                $product->update(['images' => $images]);
            }
        }
    }

    public function down()
    {
        // Reverse migration if needed
        $products = Product::whereNotNull('images')->get();

        foreach ($products as $product) {
            $images = $product->images ?? [];
            if (!empty($images) && empty($product->image_url)) {
                $product->update(['image_url' => $images[0]['url'] ?? null]);
            }
        }
    }
};