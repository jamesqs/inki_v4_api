<?php

namespace App\Modules\Media\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
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
            'name' => $this->name,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size' => $this->size,
            'human_readable_size' => $this->human_readable_size,
            'disk' => $this->disk,
            'path' => $this->path,
            'url' => $this->full_url,
            'collection' => $this->collection,
            'metadata' => $this->metadata,
            'mediable_type' => $this->mediable_type,
            'mediable_id' => $this->mediable_id,
            'uploaded_by' => $this->uploaded_by,
            'order' => $this->order,
            'is_image' => $this->isImage(),
            'is_video' => $this->isVideo(),
            'is_document' => $this->isDocument(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
