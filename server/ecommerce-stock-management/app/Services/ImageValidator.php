<?php
// filepath: server/ecommerce-stock-management/app/Services/ImageValidator.php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ImageValidator
{
    /**
     * Allowed MIME types (whitelist approach)
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Allowed file extensions
     */
    private const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
    ];

        /**
         * Maximum file size (10MB in bytes per image)
         */
        private const MAX_FILE_SIZE = 10 * 1024 * 1024;

        /**
         * Maximum number of images allowed
         */
        private const MAX_IMAGES = 5;


    /**
     * Validate uploaded image file
     * 
     * @param UploadedFile $file
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public static function validate(UploadedFile $file): array
    {
        // 1. Check if file exists
        if (!$file->isValid()) {
            return [
                'valid' => false,
                'error' => 'File upload failed or is corrupt'
            ];
        }

        // 2. Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum allowed (5MB)'
            ];
        }

        // 3. Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            Log::warning('Blocked file with invalid extension', [
                'extension' => $extension,
                'filename' => $file->getClientOriginalName()
            ]);

            return [
                'valid' => false,
                'error' => 'File type not allowed. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS)
            ];
        }

        // 4. Check MIME type (server-side verification)
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            Log::warning('Blocked file with invalid MIME type', [
                'mime_type' => $mimeType,
                'extension' => $extension,
                'filename' => $file->getClientOriginalName()
            ]);

            return [
                'valid' => false,
                'error' => 'Invalid file format detected'
            ];
        }

        // 5. Verify file is actually an image (read file headers)
        if (!self::isValidImageFile($file)) {
            Log::warning('Blocked non-image file disguised as image', [
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $mimeType
            ]);

            return [
                'valid' => false,
                'error' => 'File is not a valid image'
            ];
        }

        // 6. Check for suspicious filenames
        if (self::hasSuspiciousFilename($file->getClientOriginalName())) {
            Log::warning('Blocked file with suspicious filename', [
                'filename' => $file->getClientOriginalName()
            ]);

            return [
                'valid' => false,
                'error' => 'Invalid filename'
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Verify file is actually an image by reading its content
     * 
     * @param UploadedFile $file
     * @return bool
     */
    private static function isValidImageFile(UploadedFile $file): bool
    {
        try {
            // Use getimagesize to verify file is actually an image
            $imageInfo = @getimagesize($file->getRealPath());

            if ($imageInfo === false) {
                return false;
            }

            // Verify MIME type matches image type
            $detectedMime = $imageInfo['mime'] ?? null;

            return in_array($detectedMime, self::ALLOWED_MIME_TYPES);
        } catch (\Exception $e) {
            Log::error('Error validating image file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check for suspicious filename patterns
     * 
     * @param string $filename
     * @return bool
     */
    private static function hasSuspiciousFilename(string $filename): bool
    {
        // Check for path traversal attempts
        if (
            str_contains($filename, '..') ||
            str_contains($filename, '/') ||
            str_contains($filename, '\\')
        ) {
            return true;
        }

        // Check for null bytes
        if (str_contains($filename, "\0")) {
            return true;
        }

        // Check for executable extensions hidden in filename
        $dangerousPatterns = [
            '.php',
            '.phtml',
            '.php3',
            '.php4',
            '.php5',
            '.exe',
            '.sh',
            '.bat',
            '.cmd',
            '.com',
            '.js',
            '.jar',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (stripos($filename, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate secure filename
     * 
     * @param UploadedFile $file
     * @return string
     */
    public static function generateSecureFilename(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        // Generate random filename to prevent guessing
        $randomName = bin2hex(random_bytes(16));
        $timestamp = time();

        return "{$timestamp}_{$randomName}.{$extension}";
    }

    /**
     * Sanitize original filename for storage
     * 
     * @param string $filename
     * @return string
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove any path components
        $filename = basename($filename);

        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Limit length
        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $filename = substr($filename, 0, 255 - strlen($extension) - 1) . '.' . $extension;
        }

        return $filename;
    }

    /**
     * Get allowed MIME types
     * 
     * @return array
     */
    public static function getAllowedMimeTypes(): array
    {
        return self::ALLOWED_MIME_TYPES;
    }

    /**
     * Get allowed extensions
     * 
     * @return array
     */
    public static function getAllowedExtensions(): array
    {
        return self::ALLOWED_EXTENSIONS;
    }

    /**
     * Get max file size in bytes
     * 
     * @return int
     */
    public static function getMaxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }

    /**
     * Get max file size in human-readable format
     * 
     * @return string
     */
    public static function getMaxFileSizeFormatted(): string
    {
        return number_format(self::MAX_FILE_SIZE / 1024 / 1024, 0) . 'MB';
    }
}