<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaService
{
    protected $disk;
    protected $imageManager;

    // Image quality settings
    const IMAGE_QUALITY = 80;  // Reduced quality for smaller file size
    const THUMBNAIL_SIZE = 300;
    const MEDIUM_SIZE = 800;  // Primary size - good balance of quality and size

    // Max file sizes (in bytes)
    const MAX_IMAGE_SIZE = 10 * 1024 * 1024; // 10MB
    const MAX_VIDEO_SIZE = 100 * 1024 * 1024; // 100MB

    public function __construct()
    {
        $this->disk = Storage::disk('wasabi');
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Generate Wasabi URL for a given path
     */
    protected function getWasabiUrl(string $path): string
    {
        $bucket = config('filesystems.disks.wasabi.bucket');
        $region = config('filesystems.disks.wasabi.region', 'ap-southeast-1');

        // Use virtual-hosted style URL: https://bucket.region.wasabisys.com/path
        return "https://{$bucket}.{$region}.wasabisys.com/{$path}";
    }

    /**
     * Upload and optimize an image
     * Only saves medium (800px) and thumbnail (300px) versions for optimal storage
     */
    public function uploadImage(UploadedFile $file, string $folder = 'images'): array
    {
        // Validate file
        $this->validateImage($file);

        // Generate unique filename
        $filename = Str::random(40);
        $extension = $file->getClientOriginalExtension();

        // Read and process image
        $image = $this->imageManager->read($file->getRealPath());

        $urls = [];

        // Medium version (800px max) - Primary version with good quality and reasonable size
        $mediumPath = "{$folder}/medium/{$filename}.{$extension}";
        $medium = $this->optimizeImage($image, self::MEDIUM_SIZE);
        $this->disk->put($mediumPath, $medium, 'public');
        $urls['medium'] = $this->getWasabiUrl($mediumPath);

        // Thumbnail (300px max) - For previews and cards
        $thumbnailPath = "{$folder}/thumbnails/{$filename}.{$extension}";
        $thumbnail = $this->optimizeImage($image, self::THUMBNAIL_SIZE);
        $this->disk->put($thumbnailPath, $thumbnail, 'public');
        $urls['thumbnail'] = $this->getWasabiUrl($thumbnailPath);

        return [
            'url' => $urls['medium'], // Return medium version as primary URL (best balance)
            'urls' => $urls,
            'filename' => "{$filename}.{$extension}",
            'path' => $mediumPath, // Store medium path as primary
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'width' => $image->width(),
            'height' => $image->height(),
        ];
    }

    /**
     * Optimize image with quality and size constraints
     */
    protected function optimizeImage($image, ?int $maxSize = null): string
    {
        // Clone to avoid modifying original
        $optimized = clone $image;

        // Resize if needed
        if ($maxSize !== null) {
            $optimized->scale(width: $maxSize, height: $maxSize);
        }

        // Encode with quality settings
        return $optimized->toJpeg(quality: self::IMAGE_QUALITY)->toString();
    }

    /**
     * Upload video to Wasabi
     */
    public function uploadVideo(UploadedFile $file, string $folder = 'videos'): array
    {
        // Validate file
        $this->validateVideo($file);

        // Generate unique filename
        $filename = Str::random(40);
        $extension = $file->getClientOriginalExtension();
        $path = "{$folder}/{$filename}.{$extension}";

        // Upload to Wasabi
        $this->disk->put($path, file_get_contents($file->getRealPath()), 'public');

        return [
            'url' => $this->getWasabiUrl($path),
            'filename' => "{$filename}.{$extension}",
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }

    /**
     * Upload any file to Wasabi
     */
    public function uploadFile(UploadedFile $file, string $folder = 'documents'): array
    {
        // Generate unique filename
        $filename = Str::random(40);
        $extension = $file->getClientOriginalExtension();
        $path = "{$folder}/{$filename}.{$extension}";

        // Upload to Wasabi
        $this->disk->put($path, file_get_contents($file->getRealPath()), 'public');

        return [
            'url' => $this->getWasabiUrl($path),
            'filename' => "{$filename}.{$extension}",
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }

    /**
     * Delete file from Wasabi
     */
    public function deleteFile(string $path): bool
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $path = $this->extractPathFromUrl($path) ?? $path;
        }
        return $this->disk->delete($path);
    }

    /**
     * Delete image and all its versions
     */
    public function deleteImage(string $imagePath): bool
    {
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            $imagePath = $this->extractPathFromUrl($imagePath) ?? $imagePath;
        }
        $deleted = true;

        // Extract base path
        $pathInfo = pathinfo($imagePath);
        $folder = dirname(dirname($imagePath));
        $filename = $pathInfo['basename'];

        // Delete both versions (medium and thumbnails only)
        $versions = ['medium', 'thumbnails'];
        foreach ($versions as $version) {
            $path = "{$folder}/{$version}/{$filename}";
            if ($this->disk->exists($path)) {
                $deleted = $this->disk->delete($path) && $deleted;
            }
        }

        return $deleted;
    }

    /**
     * Validate image file
     */
    protected function validateImage(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($mimeType, $allowedMimes)) {
            throw new \InvalidArgumentException('Invalid image type. Allowed: JPEG, PNG, GIF, WebP');
        }

        if ($file->getSize() > self::MAX_IMAGE_SIZE) {
            throw new \InvalidArgumentException('Image size exceeds maximum allowed size of 10MB');
        }
    }

    /**
     * Validate video file
     */
    protected function validateVideo(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();
        $allowedMimes = ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/webm'];

        if (!in_array($mimeType, $allowedMimes)) {
            throw new \InvalidArgumentException('Invalid video type. Allowed: MP4, MPEG, MOV, AVI, WebM');
        }

        if ($file->getSize() > self::MAX_VIDEO_SIZE) {
            throw new \InvalidArgumentException('Video size exceeds maximum allowed size of 100MB');
        }
    }

    /**
     * Get file URL from path
     */
    public function getUrl(string $path): string
    {
        return $this->getWasabiUrl($path);
    }

    /**
     * Check if file exists
     */
    public function exists(string $path): bool
    {
        return $this->disk->exists($path);
    }

    /**
     * Extract storage path from full Wasabi URL
     */
    public function extractPathFromUrl(string $url): ?string
    {
        $bucket = config('filesystems.disks.wasabi.bucket');
        $endpoint = config('filesystems.disks.wasabi.endpoint');

        // Handle different URL patterns
        // Pattern 1: https://bucket.region.wasabisys.com/images/medium/filename.jpg
        $pattern1 = '/^https?:\/\/' . preg_quote($bucket, '/') . '\..+\.wasabisys\.com\/(.+)$/';
        if (preg_match($pattern1, $url, $matches)) {
            return $matches[1];
        }

        // Pattern 2: https://endpoint/bucket/images/medium/filename.jpg
        if ($endpoint) {
            $endpoint = rtrim($endpoint, '/');
            $pattern2 = '/^' . preg_quote($endpoint, '/') . '\/' . preg_quote($bucket, '/') . '\/(.+)$/';
            if (preg_match($pattern2, $url, $matches)) {
                return $matches[1];
            }
        }

        // Pattern 3: Try to extract from query parameters (fallback)
        $parsedUrl = parse_url($url);
        if (isset($parsedUrl['path'])) {
            $path = $parsedUrl['path'];
            // Remove leading slashes
            $path = ltrim($path, '/');
            // Try to remove bucket prefix if present
            if (strpos($path, $bucket . '/') === 0) {
                return substr($path, strlen($bucket) + 1);
            }
            return $path;
        }

        return null;
    }
}
