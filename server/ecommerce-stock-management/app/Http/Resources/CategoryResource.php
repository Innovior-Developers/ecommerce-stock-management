<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->generatePublicId($this->_id), // ✅ Hash MongoDB ID
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'status' => $this->status,
            'sort_order' => (int) ($this->sort_order ?? 0),
            'parent_id' => $this->parent_id ? $this->generatePublicId($this->parent_id) : null,
            'image_url' => $this->image_url,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'products_count' => $this->products_count ?? 0,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            // ❌ Don't expose: _id
        ];
    }

    private function generatePublicId($mongoId)
    {
        return 'cat_' . substr(hash('sha256', (string)$mongoId), 0, 16);
    }
}
