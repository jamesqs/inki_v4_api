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
        return new BlogPostResource($blogPost);
    }

    /**
     * Display the specified resource.
     */
    public function show(BlogPost $blogPost): BlogPostResource
    {
        return new BlogPostResource($blogPost);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BlogPostRequest $request, BlogPost $blogPost): BlogPostResource
    {
        $validated = $request->validated();

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']) . '-' . now()->format('Ymd');
        }

        $blogPost->update($validated);
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
}