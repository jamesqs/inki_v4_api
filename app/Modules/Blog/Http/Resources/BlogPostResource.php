<?php

namespace App\Modules\Blog\Http\Resources;

use App\Modules\Media\Http\Resources\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogPostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'category_id' => $this->category_id,
            'user_id' => $this->user_id,
            'published' => $this->published,
            'featured' => $this->featured,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'category' => $this->whenLoaded('category'),
            'author' => $this->whenLoaded('author'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'featured_image' => new MediaResource($this->whenLoaded('featuredImage'))
        ];
    }
}