<?php

namespace App\Modules\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'name',
        'file_name',
        'mime_type',
        'extension',
        'size',
        'disk',
        'path',
        'url',
        'collection',
        'metadata',
        'mediable_type',
        'mediable_id',
        'uploaded_by',
        'order',
    ];

    protected $casts = [
        'metadata' => 'array',
        'size' => 'integer',
        'order' => 'integer',
    ];

    protected $appends = [
        'human_readable_size',
    ];

    /**
     * Get the owning mediable model (polymorphic relation).
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded the file.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Users\Models\User::class, 'uploaded_by');
    }

    /**
     * Get the full URL to the media file.
     */
    public function getFullUrlAttribute(): string
    {
        return $this->url ?: Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Delete the file from storage when the model is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (Media $media) {
            if ($media->isForceDeleting()) {
                // Permanently delete file from storage
                Storage::disk($media->disk)->delete($media->path);
            }
        });
    }

    /**
     * Check if media is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if media is a video.
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if media is a document.
     */
    public function isDocument(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
