<?php

namespace App\Modules\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Modules\Categories\Models\Category;
use App\Modules\Users\Models\User;

/**
 * @method static paginate()
 * @method static create(mixed $validated)
 */

class BlogPost extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Retrieve the model for a bound value.
     * Supports both ID and slug-based lookups.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // If field is explicitly specified (e.g., {blogPost:id}), use it
        if ($field) {
            return $this->where($field, $value)->first();
        }

        // Try to find by ID first (if value is numeric)
        if (is_numeric($value)) {
            $model = $this->where('id', $value)->first();
            if ($model) {
                return $model;
            }
        }

        // Fall back to slug
        return $this->where('slug', $value)->first();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title', 'slug', 'content', 'excerpt', 'category_id', 'user_id',
        'published', 'featured', 'meta_title', 'meta_description', 'published_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published' => 'boolean',
        'featured' => 'boolean',
        'published_at' => 'datetime'
    ];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all media for the blog post.
     */
    public function media(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Modules\Media\Models\Media::class, 'mediable')->orderBy('order');
    }

    /**
     * Get the featured image (first image in media).
     */
    public function featuredImage(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(\App\Modules\Media\Models\Media::class, 'mediable')
            ->where('mime_type', 'like', 'image/%')
            ->orderBy('order');
    }
}