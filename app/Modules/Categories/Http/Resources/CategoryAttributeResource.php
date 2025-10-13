<?php


namespace App\Modules\Categories\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryAttributeResource extends JsonResource
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
            'display_name' => $this->display_name,
            'type' => $this->type,
            'options' => $this->options,
            'pivot' => [
                'required' => $this->pivot->required,
                'order' => $this->pivot->order,
                'created_at' => $this->pivot->created_at,
                'updated_at' => $this->pivot->updated_at,
            ],
        ];
    }
}
