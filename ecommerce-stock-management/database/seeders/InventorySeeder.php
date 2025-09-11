<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory;
use App\Models\Product;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        foreach ($products as $product) {
            Inventory::create([
                'product_id' => (string) $product->_id,
                'location_id' => 'warehouse_001',
                'qty_on_hand' => $product->stock_quantity,
                'qty_reserved' => 0,
                'qty_available' => $product->stock_quantity,
                'reorder_level' => 10,
                'reorder_quantity' => 50,
                'last_movement_date' => now(),
            ]);
        }
    }
}