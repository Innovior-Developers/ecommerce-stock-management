<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TestS3Upload extends Command
{
    protected $signature = 's3:test';
    protected $description = 'Test S3 upload functionality';

    public function handle()
    {
        $this->info('Testing S3 Configuration...');

        // Display current config
        $this->info('Current filesystem disk: ' . config('filesystems.default'));
        $this->info('S3 Bucket: ' . config('filesystems.disks.s3.bucket'));
        $this->info('S3 Region: ' . config('filesystems.disks.s3.region'));

        try {
            // Test upload
            $testContent = 'Hello from Laravel at ' . now()->toDateTimeString();
            $filename = 'test-' . time() . '.txt';
            
            $this->info("Uploading test file: {$filename}");
            
            $path = Storage::disk('s3')->put("test/{$filename}", $testContent);
            
            if ($path) {
                $url = Storage::disk('s3')->url("test/{$filename}");
                $this->info("✅ Upload successful!");
                $this->info("File path: {$path}");
                $this->info("URL: {$url}");

                // Verify file exists
                if (Storage::disk('s3')->exists("test/{$filename}")) {
                    $this->info("✅ File verified in S3");
                    
                    // Read content back
                    $content = Storage::disk('s3')->get("test/{$filename}");
                    $this->info("Content: {$content}");
                } else {
                    $this->error("❌ File not found in S3");
                }
            } else {
                $this->error("❌ Upload failed");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            Log::error('S3 Test Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }
}