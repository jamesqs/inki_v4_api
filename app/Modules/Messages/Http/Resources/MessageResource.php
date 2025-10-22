<?php

namespace App\Modules\Messages\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estate_id' => $this->estate_id,
            'message' => $this->message,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'is_read' => $this->isRead(),

            // Sender info (sanitized)
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'profile_picture' => $this->sender->profilePicture ? [
                    'url' => $this->sender->profilePicture->url,
                    'name' => $this->sender->profilePicture->name,
                ] : null,
            ],

            // Receiver info (sanitized)
            'receiver' => [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name,
                'profile_picture' => $this->receiver->profilePicture ? [
                    'url' => $this->receiver->profilePicture->url,
                    'name' => $this->receiver->profilePicture->name,
                ] : null,
            ],

            // Estate info (when loaded)
            'estate' => $this->when($this->relationLoaded('estate'), function() {
                return [
                    'id' => $this->estate->id,
                    'name' => $this->estate->name,
                    'slug' => $this->estate->slug,
                ];
            }),
        ];
    }
}
