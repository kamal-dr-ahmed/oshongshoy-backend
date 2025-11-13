<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
