<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Product extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'products';

    protected $fillable = [
        'name',
        'description',
        'price',
        'sku',
        'category',
        'stock_quantity',
        'status',
        'images',
        'weight',
        'dimensions',
        'meta_title',
        'meta_description',
        'public_id', // ✅ Add this
    ];

    protected $hidden = [
        '_id', // ✅ Hide MongoDB ID from responses
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'weight' => 'decimal:2',
        'dimensions' => 'array',
        'images' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ✅ Boot method - Add public_id generation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            // Generate public_id for security
            if (!$product->public_id) {
                $product->public_id = 'prod_' . Str::random(20);
            }

            // Auto-generate SKU
            if (empty($product->sku)) {
                $product->sku = $product->generateSku();
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty(['name', 'meta_title']) && empty($product->sku)) {
                $product->sku = $product->generateSku();
            }
        });

        static::deleting(function ($product) {
            $product->deleteImages();
        });
    }

    // ✅ Generate hashed public ID for frontend (security)
    public function getHashedIdAttribute()
    {
        return 'prod_' . substr(hash('sha256', (string)$this->_id), 0, 16);
    }

    // Generate SKU from product name and meta title
    public function generateSku()
    {
        $name = Str::slug($this->name ?? '', '');
        $metaTitle = Str::slug($this->meta_title ?? '', '');

        $baseSku = strtoupper(substr($name, 0, 6) . substr($metaTitle, 0, 4));

        if (strlen($baseSku) < 3) {
            $baseSku = 'PROD';
        }

        $timestamp = now()->format('md');
        $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

        $sku = $baseSku . $timestamp . $random;

        $originalSku = $sku;
        $counter = 1;

        while (static::where('sku', $sku)->where('_id', '!=', $this->_id ?? null)->exists()) {
            $sku = $originalSku . str_pad($counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $sku;
    }

    // Upload image to S3 - Your existing method (unchanged)
    public function uploadImage($file, $existingImages = [])
    {
        Log::info('uploadImage called', [
            'file_valid' => $file && $file->isValid(),
            'file_name' => $file ? $file->getClientOriginalName() : 'null',
            'file_size' => $file ? $file->getSize() : 'null',
            'existing_images_count' => count($existingImages)
        ]);

        if (!$file || !$file->isValid()) {
            Log::warning('Invalid file provided to uploadImage');
            return $existingImages;
        }

        try {
            // Ensure product has an ID
            if (!$this->_id) {
                Log::info('Product has no ID, saving first');
                $this->save();
            }

            // Generate unique filename
            $timestamp = time();
            $randomString = Str::random(8);
            $extension = $file->getClientOriginalExtension();
            $filename = $timestamp . '_' . $randomString . '.' . $extension;
            $relativePath = 'products/' . $this->_id . '/' . $filename;

            Log::info('Attempting S3 upload', [
                'product_id' => $this->_id,
                'filename' => $filename,
                'path' => $relativePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

            // Upload file to S3
            $uploadResult = Storage::disk('s3')->put($relativePath, file_get_contents($file), [
                'ContentType' => $file->getMimeType(),
                'CacheControl' => 'max-age=31536000',
            ]);

            Log::info('S3 upload result', ['success' => $uploadResult]);

            if (!$uploadResult) {
                Log::error('S3 upload failed - Storage::put returned false');
                return $existingImages;
            }

            // Construct URL
            $bucket = config('filesystems.disks.s3.bucket');
            $region = config('filesystems.disks.s3.region');
            $fullUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$relativePath}";

            Log::info('Generated S3 URL', ['url' => $fullUrl]);

            // Add to existing images array
            $images = is_array($existingImages) ? $existingImages : [];
            $newImage = [
                'url' => $fullUrl,
                'path' => $relativePath,
                'filename' => $filename,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_at' => now()->toISOString(),
                'is_primary' => empty($images),
            ];

            $images[] = $newImage;

            Log::info('Image added to array', [
                'new_image' => $newImage,
                'total_images' => count($images)
            ]);

            return $images;
        } catch (\Exception $e) {
            Log::error('Image upload exception', [
                'message' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName() ?? 'unknown',
                'product_id' => $this->_id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return $existingImages;
        }
    }

    // Upload multiple images - Your existing method (unchanged)
    public function uploadMultipleImages($files, $existingImages = [])
    {
        $images = is_array($existingImages) ? $existingImages : [];

        // Handle single file or array of files
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                $images = $this->uploadImage($file, $images);
            } else {
                Log::warning('Invalid file skipped during upload', [
                    'product_id' => $this->_id ?? 'unknown'
                ]);
            }
        }

        return $images;
    }

    // Delete images from S3 - Your existing method (unchanged)
    public function deleteImages($imagesToDelete = null)
    {
        $images = $imagesToDelete ?? $this->images ?? [];

        if (!is_array($images)) return;

        foreach ($images as $image) {
            if (isset($image['path']) && !empty($image['path'])) {
                try {
                    $deleted = Storage::disk('s3')->delete($image['path']);
                    if ($deleted) {
                        Log::info('Successfully deleted image from S3', [
                            'path' => $image['path'],
                            'product_id' => $this->_id ?? 'unknown'
                        ]);
                    } else {
                        Log::warning('Failed to delete image from S3 (file may not exist)', [
                            'path' => $image['path'],
                            'product_id' => $this->_id ?? 'unknown'
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Exception while deleting image from S3: ' . $e->getMessage(), [
                        'path' => $image['path'] ?? 'unknown',
                        'product_id' => $this->_id ?? 'unknown',
                        'exception' => $e->getTraceAsString()
                    ]);
                }
            }
        }
    }

    // Delete specific image - Your existing method (unchanged)
    public function deleteImage($imageIndex)
    {
        $images = $this->images ?? [];

        if (!isset($images[$imageIndex])) {
            Log::warning('Attempted to delete non-existent image', [
                'image_index' => $imageIndex,
                'product_id' => $this->_id ?? 'unknown',
                'total_images' => count($images)
            ]);
            return false;
        }

        $imageToDelete = $images[$imageIndex];

        // Delete from S3
        if (isset($imageToDelete['path']) && !empty($imageToDelete['path'])) {
            try {
                $deleted = Storage::disk('s3')->delete($imageToDelete['path']);
                if ($deleted) {
                    Log::info('Successfully deleted single image from S3', [
                        'path' => $imageToDelete['path'],
                        'product_id' => $this->_id
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to delete single image from S3: ' . $e->getMessage(), [
                    'path' => $imageToDelete['path'] ?? 'unknown',
                    'product_id' => $this->_id ?? 'unknown'
                ]);
            }
        }

        // Remove from array
        unset($images[$imageIndex]);
        $images = array_values($images); // Re-index array

        // Update primary image if needed
        if (isset($imageToDelete['is_primary']) && $imageToDelete['is_primary'] && !empty($images)) {
            $images[0]['is_primary'] = true;

            // Reset other images' primary status
            for ($i = 1; $i < count($images); $i++) {
                $images[$i]['is_primary'] = false;
            }
        }

        // Update the database
        $this->update(['images' => $images]);

        return true;
    }

    // Set primary image - Your existing method (unchanged)
    public function setPrimaryImage($imageIndex)
    {
        $images = $this->images ?? [];

        if (!isset($images[$imageIndex])) {
            return false;
        }

        // Reset all primary flags
        foreach ($images as $key => $image) {
            $images[$key]['is_primary'] = ($key == $imageIndex);
        }

        $this->update(['images' => $images]);
        return true;
    }

    // Get primary image - Your existing method (unchanged)
    public function getPrimaryImageAttribute()
    {
        $images = $this->images ?? [];

        if (empty($images)) {
            return null;
        }

        // Find primary image
        foreach ($images as $image) {
            if (isset($image['is_primary']) && $image['is_primary'] && isset($image['url'])) {
                return $image['url'];
            }
        }

        // Return first image if no primary set
        return $images[0]['url'] ?? null;
    }

    // Get all image URLs - Your existing method (unchanged)
    public function getImageUrlsAttribute()
    {
        $images = $this->images ?? [];
        return array_column($images, 'url');
    }

    // Get image count - Your existing method (unchanged)
    public function getImageCountAttribute()
    {
        return count($this->images ?? []);
    }

    // Check if product has images - Your existing method (unchanged)
    public function getHasImagesAttribute()
    {
        return !empty($this->images);
    }

    // Relationships
    public function inventory()
    {
        return $this->hasOne(\App\Models\Inventory::class, 'product_id', '_id');
    }

    public function category_model()
    {
        return $this->belongsTo(\App\Models\Category::class, 'category', 'name');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeLowStock($query, $threshold = 10)
    {
        return $query->where('stock_quantity', '<=', $threshold);
    }

    // Accessors
    public function getIsInStockAttribute()
    {
        return $this->stock_quantity > 0;
    }

    public function getIsLowStockAttribute()
    {
        return $this->stock_quantity <= 10 && $this->stock_quantity > 0;
    }
}