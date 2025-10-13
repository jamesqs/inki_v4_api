<?php

namespace App\Modules\Companies\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'city' => $this->city,
            'county' => $this->county,
            'zip' => $this->zip,
            'website' => $this->website,
            'vat_number' => $this->vat_number,
            'registration_number' => $this->registration_number,
            // Add your resource fields here
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
