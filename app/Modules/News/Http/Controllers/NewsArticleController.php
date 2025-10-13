<?php

namespace App\Modules\News\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\News\Models\NewsArticle;
use App\Modules\News\Http\Requests\NewsArticleRequest;
use App\Modules\News\Http\Resources\NewsArticleResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class NewsArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $query = NewsArticle::query();

        // If you need to search by specific fields
        if ($title = request('title')) {
            $query->where('title', 'like', "%{$title}%");
        }

        if ($published = request('published')) {
            $query->where('published', $published);
        }

        if ($breaking = request('breaking')) {
            $query->where('breaking', $breaking);
        }

        // if the get parameter raw is present, return all articles without pagination
        if (request()->has('raw')) {
            $newsArticles = $query->get();
            return NewsArticleResource::collection($newsArticles);
        }

        $newsArticles = $query->paginate();
        return NewsArticleResource::collection($newsArticles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NewsArticleRequest $request): \Illuminate\Http\JsonResponse|NewsArticleResource
    {
        $validated = $request->validated();

        // Check if article with same title already exists
        if (NewsArticle::where('title', $validated['title'])->exists()) {
            return response()->json([
                'message' => 'News article already exists with this title.'
            ], 422);
        }

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']) . '-' . now()->format('Ymd');
        }

        $newsArticle = NewsArticle::create($validated);
        return new NewsArticleResource($newsArticle);
    }

    /**
     * Display the specified resource.
     */
    public function show(NewsArticle $newsArticle): NewsArticleResource
    {
        return new NewsArticleResource($newsArticle);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NewsArticleRequest $request, NewsArticle $newsArticle): NewsArticleResource
    {
        $validated = $request->validated();

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']) . '-' . now()->format('Ymd');
        }

        $newsArticle->update($validated);
        return new NewsArticleResource($newsArticle);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(NewsArticle $newsArticle): Response
    {
        $newsArticle->delete();
        return response()->noContent();
    }
}