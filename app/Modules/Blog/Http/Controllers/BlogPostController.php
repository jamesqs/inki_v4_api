<?php

namespace App\Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Blog\Models\BlogPost;
use App\Modules\Blog\Http\Requests\BlogPostRequest;
use App\Modules\Blog\Http\Resources\BlogPostResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class BlogPostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $query = BlogPost::query();

        // Handle eager loading via ?include parameter
        if ($include = request('include')) {
            $relations = explode(',', $include);
            $allowed = ['category', 'author', 'media', 'featuredImage'];
            $relations = array_intersect($relations, $allowed);
            if (!empty($relations)) {
                $query->with($relations);
            }
        }

        // If you need to search by specific fields
        if ($title = request('title')) {
            $query->where('title', 'like', "%{$title}%");
        }

        if ($published = request('published')) {
            $query->where('published', $published);
        }

        // if the get parameter raw is present, return all posts without pagination
        if (request()->has('raw')) {
            $blogPosts = $query->get();
            return BlogPostResource::collection($blogPosts);
        }

        $blogPosts = $query->paginate();
        return BlogPostResource::collection($blogPosts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BlogPostRequest $request): \Illuminate\Http\JsonResponse|BlogPostResource
    {
        $validated = $request->validated();

        // Check if post with same title already exists
        if (BlogPost::where('title', $validated['title'])->exists()) {
            return response()->json([
                'message' => 'Blog post already exists with this title.'
            ], 422);
        }

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']) . '-' . now()->format('Ymd');
        }

        $blogPost = BlogPost::create($validated);

        // Attach media if media_ids provided
        if ($request->has('media_ids') && is_array($request->media_ids)) {
            $media = \App\Modules\Media\Models\Media::whereIn('id', $request->media_ids)->get();

            foreach ($media as $mediaItem) {
                $mediaItem->update([
                    'mediable_type' => BlogPost::class,
                    'mediable_id' => $blogPost->id
                ]);
            }

            $blogPost->load('media');
        }

        return new BlogPostResource($blogPost);
    }

    /**
     * Display the specified resource.
     */
    public function show(BlogPost $blogPost): BlogPostResource
    {
        // Handle eager loading via ?include parameter
        if ($include = request('include')) {
            $relations = explode(',', $include);
            $allowed = ['category', 'author', 'media', 'featuredImage'];
            $relations = array_intersect($relations, $allowed);
            if (!empty($relations)) {
                $blogPost->load($relations);
            }
        }

        return new BlogPostResource($blogPost);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BlogPostRequest $request, BlogPost $blogPost): BlogPostResource
    {
        $validated = $request->validated();

        // Generate slug if title changed and no slug provided
        if (isset($validated['title']) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']) . '-' . now()->format('Ymd');
        }

        // Remove media_ids from validated data before update
        $mediaIds = $validated['media_ids'] ?? null;
        unset($validated['media_ids']);

        $blogPost->update($validated);

        // Handle media updates if media_ids provided
        if ($request->has('media_ids') && is_array($request->media_ids)) {
            // First, detach all existing media
            \App\Modules\Media\Models\Media::where('mediable_id', $blogPost->id)
                ->where('mediable_type', BlogPost::class)
                ->update([
                    'mediable_id' => null,
                    'mediable_type' => null
                ]);

            // Then attach new media
            if (!empty($request->media_ids)) {
                $media = \App\Modules\Media\Models\Media::whereIn('id', $request->media_ids)->get();

                foreach ($media as $mediaItem) {
                    $mediaItem->update([
                        'mediable_type' => BlogPost::class,
                        'mediable_id' => $blogPost->id
                    ]);
                }
            }

            $blogPost->load('media');
        }

        return new BlogPostResource($blogPost);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BlogPost $blogPost): Response
    {
        $blogPost->delete();
        return response()->noContent();
    }

    /**
     * Attach media to blog post.
     */
    public function attachMedia(BlogPost $blogPost, \Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'media_ids' => 'required|array',
            'media_ids.*' => 'exists:media,id'
        ]);

        $media = \App\Modules\Media\Models\Media::whereIn('id', $validated['media_ids'])->get();

        foreach ($media as $mediaItem) {
            $mediaItem->update([
                'mediable_type' => BlogPost::class,
                'mediable_id' => $blogPost->id
            ]);
        }

        $blogPost->load('media');

        return response()->json([
            'success' => true,
            'message' => 'Media attached successfully',
            'data' => new BlogPostResource($blogPost)
        ]);
    }

    /**
     * Detach media from blog post.
     */
    public function detachMedia(BlogPost $blogPost, \Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'media_ids' => 'required|array',
            'media_ids.*' => 'exists:media,id'
        ]);

        \App\Modules\Media\Models\Media::whereIn('id', $validated['media_ids'])
            ->where('mediable_id', $blogPost->id)
            ->where('mediable_type', BlogPost::class)
            ->update([
                'mediable_id' => null,
                'mediable_type' => null
            ]);

        $blogPost->load('media');

        return response()->json([
            'success' => true,
            'message' => 'Media detached successfully',
            'data' => new BlogPostResource($blogPost)
        ]);
    }
}