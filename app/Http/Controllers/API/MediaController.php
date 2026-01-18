<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MediaController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Upload image
     */
    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:10240', // 10MB
            'folder' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $folder = $request->input('folder', 'images');
            $result = $this->mediaService->uploadImage($request->file('file'), $folder);

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Public proxy endpoint to serve Wasabi-stored media even when the bucket blocks direct public access.
     */
    public function view(Request $request)
    {
        $data = $request->validate([
            'path' => 'nullable|string',
            'url' => 'nullable|url',
        ]);

        if (empty($data['path']) && empty($data['url'])) {
            return response()->json([
                'success' => false,
                'message' => 'Path or url is required',
            ], 422);
        }

        $path = $data['path'] ?? null;
        if (!$path && !empty($data['url'])) {
            $path = $this->mediaService->extractPathFromUrl($data['url']);
        }

        if (!$path) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to resolve storage path',
            ], 422);
        }

        $path = ltrim($path, '/');
        if (Str::contains($path, ['..', '\\'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid path',
            ], 422);
        }

        try {
            $disk = Storage::disk('wasabi');

            if (!$disk->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found',
                ], 404);
            }

            // Determine MIME type from file extension or default to application/octet-stream
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mimeTypeMap = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'mp4' => 'video/mp4',
                'mpeg' => 'video/mpeg',
                'mov' => 'video/quicktime',
                'avi' => 'video/x-msvideo',
                'webm' => 'video/webm',
                'mp3' => 'audio/mpeg',
                'wav' => 'audio/wav',
                'pdf' => 'application/pdf',
            ];
            $mimeType = $mimeTypeMap[$extension] ?? 'application/octet-stream';
            $stream = $disk->readStream($path);

            if (!$stream) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to read file',
                ], 500);
            }

            return response()->stream(function () use ($stream) {
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }, 200, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=3600',
                'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            ]);
        } catch (\Exception $e) {
            Log::error('Media proxy error: ' . $e->getMessage(), [
                'path' => $path,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load media file',
            ], 500);
        }
    }

    /**
     * Upload video
     */
    public function uploadVideo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:mp4,mpeg,mov,avi,webm|max:102400', // 100MB
            'folder' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $folder = $request->input('folder', 'videos');
            $result = $this->mediaService->uploadVideo($request->file('file'), $folder);

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully',
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload video',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload any file
     */
    public function uploadFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:51200', // 50MB
            'folder' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $folder = $request->input('folder', 'documents');
            $result = $this->mediaService->uploadFile($request->file('file'), $folder);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete file
     */
    public function deleteFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
            'type' => 'nullable|in:image,video,file',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $path = $request->input('path');
            // Allow full Wasabi URLs or relative storage paths
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                $path = $this->mediaService->extractPathFromUrl($path) ?? $path;
            }
            $type = $request->input('type', 'file');

            if ($type === 'image') {
                $deleted = $this->mediaService->deleteImage($path);
            } else {
                $deleted = $this->mediaService->deleteFile($path);
            }

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'File deleted successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found or already deleted',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
