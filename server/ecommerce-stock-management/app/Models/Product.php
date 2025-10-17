<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use App\Traits\MongoIdHelper;
use App\Services\ImageValidator; // ✅ ADD THIS
use App\Services\QuerySanitizer;
use Illuminate\Support\Str;

class Product extends Model
{
    use MongoIdHelper;

    protected $connection = 'mongodb';
    protected $collection = 'products';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'category',
        'sku',
        'stock_quantity',
        'status',
        'images',
        'weight',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'weight' => 'decimal:2',
        'images' => 'array',
    ];

    // Boot method to auto-generate SKU
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
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

    /**
     * ✅ SECURE: Upload single image with validation
     */
    public function uploadImage(UploadedFile $file): ?string
    {
        try {
            // ✅ STEP 1: Validate image
            $validation = ImageValidator::validate($file);

            if (!$validation['valid']) {
                Log::warning('Image validation failed', [
                    'error' => $validation['error'],
                    'filename' => $file->getClientOriginalName()
                ]);
                throw new \Exception($validation['error']);
            }

            // ✅ STEP 2: Generate secure filename
            $filename = ImageValidator::generateSecureFilename($file);

            // ✅ STEP 3: Upload WITHOUT ACL
            $path = Storage::disk('s3')->putFileAs(
                'product-images',
                $file,
                $filename,
                [
                    // ✅ REMOVE 'visibility' - bucket policy controls access
                    // 'visibility' => 'public',
                    'ContentType' => $file->getMimeType(),
                    'CacheControl' => 'max-age=31536000', // 1 year cache
                ]
            );

            // ✅ STEP 4: Return CDN URL
            $url = Storage::disk('s3')->url($path);

            Log::info('Image uploaded successfully', [
                'product_id' => $this->_id,
                'filename' => $filename,
                'url' => $url
            ]);

            return $url;
        } catch (\Exception $e) {
            Log::error('Error uploading image', [
                'product_id' => $this->_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Upload multiple images to S3
     * 
     * @param array $files Array of UploadedFile instances
     * @param array $existingImages Array of existing image data to preserve
     * @return array Array of image data with URLs
     */
    public function uploadMultipleImages(array $files, array $existingImages = []): array
    {
        Log::info('uploadMultipleImages called', [
            'product_id' => $this->_id,
            'new_files_count' => count($files),
            'existing_images_count' => count($existingImages)
        ]);

        $images = $existingImages; // Start with existing images

        foreach ($files as $index => $file) {
            try {
                // ✅ Validate the file
                $validation = ImageValidator::validate($file);

                if (!$validation['valid']) {
                    Log::error("Image validation failed at index {$index}", [
                        'error' => $validation['error'],
                        'file' => $file->getClientOriginalName()
                    ]);
                    continue; // Skip invalid images
                }

                Log::info("Uploading image {$index}: " . $file->getClientOriginalName());

                // ✅ Generate secure filename
                $filename = ImageValidator::generateSecureFilename($file);

                // ✅ FIX: Upload WITHOUT ACL (bucket policy controls access)
                $path = Storage::disk('s3')->putFileAs(
                    'product-images',
                    $file,
                    $filename,
                    [
                        // ✅ REMOVE 'visibility' - it sets ACLs which your bucket blocks
                        // 'visibility' => 'public',
                        'ContentType' => $file->getMimeType(),
                        'CacheControl' => 'max-age=31536000',
                    ]
                );

                // ✅ Get the full URL
                $url = Storage::disk('s3')->url($path);

                Log::info('Image uploaded successfully', [
                    'index' => $index,
                    'filename' => $filename,
                    'path' => $path,
                    'url' => $url
                ]);

                // ✅ Add to images array
                $images[] = [
                    'url' => $url,
                    'path' => $path,
                    'filename' => $file->getClientOriginalName(),
                    'uploaded_at' => now()->toIso8601String(),
                    'is_primary' => count($images) === 0, // First image is primary
                ];
            } catch (\Exception $e) {
                Log::error('Failed to upload image at index ' . $index, [
                    'error' => $e->getMessage(),
                    'file' => $file->getClientOriginalName()
                ]);
                // Continue with other images
            }
        }

        Log::info('uploadMultipleImages completed', [
            'product_id' => $this->_id,
            'total_images' => count($images)
        ]);

        return $images;
    }

    /**
     * Delete all product images
     */
    public function deleteImages(): void
    {
        if (empty($this->images)) {
            return;
        }

        foreach ($this->images as $imageUrl) {
            try {
                $path = $this->extractS3Path($imageUrl);
                if ($path && Storage::disk('s3')->exists($path)) {
                    Storage::disk('s3')->delete($path);
                    Log::info('Deleted image', [
                        'product_id' => $this->_id,
                        'path' => $path
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error deleting image', [
                    'product_id' => $this->_id,
                    'image_url' => $imageUrl,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Delete single image by index
     */
    public function deleteImage(int $index): void
    {
        if (!isset($this->images[$index])) {
            throw new \Exception('Image index not found');
        }

        $imageUrl = $this->images[$index];
        $path = $this->extractS3Path($imageUrl);

        if ($path && Storage::disk('s3')->exists($path)) {
            Storage::disk('s3')->delete($path);
        }

        $images = $this->images;
        unset($images[$index]);
        $this->images = array_values($images);
        $this->save();
    }

    /**
     * Extract S3 path from full URL
     */
    private function extractS3Path(string $url): ?string
    {
        $bucket = config('filesystems.disks.s3.bucket');
        $region = config('filesystems.disks.s3.region');

        $patterns = [
            "https://{$bucket}.s3.{$region}.amazonaws.com/",
            "https://s3.{$region}.amazonaws.com/{$bucket}/",
        ];

        foreach ($patterns as $pattern) {
            if (str_starts_with($url, $pattern)) {
                return str_replace($pattern, '', $url);
            }
        }

        return null;
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

    public function scopeByIds($query, array $ids)
    {
        $validIds = QuerySanitizer::sanitizeMongoIds($ids);
        return $query->whereIn('_id', $validIds);
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

    // Override primary key handling
    public function getRouteKeyName()
    {
        return '_id';
    }
}
