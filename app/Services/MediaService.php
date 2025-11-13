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
    const IMAGE_QUALITY = 85;
    const THUMBNAIL_SIZE = 300;
    const MEDIUM_SIZE = 800;
    const LARGE_SIZE = 1920;
    
    // Max file sizes (in bytes)
    const MAX_IMAGE_SIZE = 10 * 1024 * 1024; // 10MB
    const MAX_VIDEO_SIZE = 100 * 1024 * 1024; // 100MB
    
    public function __construct()
    {
        $this->disk = Storage::disk('wasabi');
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Upload and optimize an image
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
        
        // Original (optimized)
        $originalPath = "{$folder}/original/{$filename}.{$extension}";
        $optimized = $this->optimizeImage($image, null);
        $this->disk->put($originalPath, $optimized, 'public');
        $urls['original'] = $this->disk->url($originalPath);
        
        // Large version (1920px max)
        if ($image->width() > self::LARGE_SIZE || $image->height() > self::LARGE_SIZE) {
            $largePath = "{$folder}/large/{$filename}.{$extension}";
            $large = $this->optimizeImage($image, self::LARGE_SIZE);
            $this->disk->put($largePath, $large, 'public');
            $urls['large'] = $this->disk->url($largePath);
        }
        
        // Medium version (800px max)
        $mediumPath = "{$folder}/medium/{$filename}.{$extension}";
        $medium = $this->optimizeImage($image, self::MEDIUM_SIZE);
        $this->disk->put($mediumPath, $medium, 'public');
        $urls['medium'] = $this->disk->url($mediumPath);
        
        // Thumbnail (300px max)
        $thumbnailPath = "{$folder}/thumbnails/{$filename}.{$extension}";
        $thumbnail = $this->optimizeImage($image, self::THUMBNAIL_SIZE);
        $this->disk->put($thumbnailPath, $thumbnail, 'public');
        $urls['thumbnail'] = $this->disk->url($thumbnailPath);
        
        return [
            'url' => $urls['original'],
            'urls' => $urls,
            'filename' => "{$filename}.{$extension}",
            'path' => $originalPath,
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
            'url' => $this->disk->url($path),
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
            'url' => $this->disk->url($path),
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
        return $this->disk->delete($path);
    }

    /**
     * Delete image and all its versions
     */
    public function deleteImage(string $originalPath): bool
    {
        $deleted = true;
        
        // Extract base path
        $pathInfo = pathinfo($originalPath);
        $folder = dirname(dirname($originalPath));
        $filename = $pathInfo['basename'];
        
        // Delete all versions
        $versions = ['original', 'large', 'medium', 'thumbnails'];
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
        return $this->disk->url($path);
    }

    /**
     * Check if file exists
     */
    public function exists(string $path): bool
    {
        return $this->disk->exists($path);
    }
}
