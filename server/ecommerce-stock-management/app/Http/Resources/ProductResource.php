<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->public_id ?? $this->hashed_id, // ✅ Use public_id or hashed_id, never _id
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'sku' => $this->sku,
            'category' => $this->category,
            'stock_quantity' => (int) $this->stock_quantity,
            'status' => $this->status ?? 'active',
            'images' => $this->images ?? [], // ✅ Full images array with S3 URLs
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'primary_image' => $this->primary_image, // ✅ Computed primary image URL
            'image_count' => $this->image_count, // ✅ Computed count
            'is_in_stock' => $this->is_in_stock, // ✅ Computed boolean
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            // ❌ DON'T expose: _id
        ];
    }
}
