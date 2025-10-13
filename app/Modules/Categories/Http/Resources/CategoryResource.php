<?php

namespace App\Modules\Categories\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'attributes' => $this->when(
                $this->relationLoaded('attributes'),
                $this->attributes->map(function ($attribute) {
                    return [
                        'id' => $attribute->id,
                        'name' => $attribute->name,
                        'display_name' => $attribute->display_name,
                        'type' => $attribute->type,
                        'options' => $attribute->options,
                        'required' => $attribute->pivot->required,
                        'order' => $attribute->pivot->order,
                    ];
                })
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
