<?php

namespace App\Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Http\Resources\MediaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Display a listing of media files.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Media::query();

        // Filter by collection
        if ($collection = $request->get('collection')) {
            $query->where('collection', $collection);
        }

        // Filter by type
        if ($type = $request->get('type')) {
            switch ($type) {
                case 'image':
                    $query->where('mime_type', 'like', 'image/%');
                    break;
                case 'video':
                    $query->where('mime_type', 'like', 'video/%');
                    break;
                case 'document':
                    $query->whereIn('mime_type', [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ]);
                    break;
            }
        }

        // Order by latest first
        $query->orderBy('created_at', 'desc');

        $media = $query->paginate($request->get('per_page', 15));

        return MediaResource::collection($media);
    }

    /**
     * Upload a new media file.
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:102400', // 100MB max
            'collection' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'mediable_type' => 'nullable|string',
            'mediable_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $collection = $request->get('collection', 'general');

            // Generate unique filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;

            // Determine storage path based on collection
            $storagePath = $this->getStoragePath($collection, $fileName);

            // Get disk from env or default to digitalocean
            // Use 'public' disk for local development so files are publicly accessible
            $disk = config('filesystems.default') === 'local' ? 'public' : 'digitalocean';

            // Upload file
            $path = $file->storeAs(
                dirname($storagePath),
                basename($storagePath),
                ['disk' => $disk]
            );

            // Get file URL
            $url = Storage::disk($disk)->url($path);

            // Create media record
            $media = Media::create([
                'name' => $originalName,
                'file_name' => $fileName,
                'mime_type' => $file->getMimeType(),
                'extension' => $extension,
                'size' => $file->getSize(),
                'disk' => $disk,
                'path' => $path,
                'url' => $url,
                'collection' => $collection,
                'metadata' => $request->get('metadata', []),
                'mediable_type' => $request->get('mediable_type'),
                'mediable_id' => $request->get('mediable_id'),
                'uploaded_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => new MediaResource($media)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File upload failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified media file.
     */
    public function show(Media $media): MediaResource
    {
        return new MediaResource($media);
    }

    /**
     * Update the specified media file metadata.
     */
    public function update(Request $request, Media $media): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'collection' => 'sometimes|string|max:255',
            'metadata' => 'sometimes|array',
            'order' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $media->update($request->only(['name', 'collection', 'metadata', 'order']));

        return response()->json([
            'success' => true,
            'message' => 'Media updated successfully',
            'data' => new MediaResource($media)
        ]);
    }

    /**
     * Remove the specified media file (soft delete).
     */
    public function destroy(Media $media): JsonResponse
    {
        $media->delete();

        return response()->json([
            'success' => true,
            'message' => 'Media deleted successfully'
        ]);
    }

    /**
     * Permanently delete the specified media file.
     */
    public function forceDestroy(Media $media): JsonResponse
    {
        $media->forceDelete(); // This will trigger the file deletion

        return response()->json([
            'success' => true,
            'message' => 'Media permanently deleted'
        ]);
    }

    /**
     * Get storage path based on collection.
     */
    private function getStoragePath(string $collection, string $fileName): string
    {
        $date = now()->format('Y/m');

        return match ($collection) {
            'estate_images' => "estates/images/{$date}/{$fileName}",
            'blog_images' => "blog/images/{$date}/{$fileName}",
            'blog_files' => "blog/files/{$date}/{$fileName}",
            'news_images' => "news/images/{$date}/{$fileName}",
            'user_avatars' => "users/avatars/{$fileName}",
            'company_logos' => "companies/logos/{$fileName}",
            default => "uploads/{$collection}/{$date}/{$fileName}",
        };
    }
}
