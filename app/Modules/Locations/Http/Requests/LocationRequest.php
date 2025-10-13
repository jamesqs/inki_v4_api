<?php

namespace App\Modules\Locations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:locations,slug,' . ($this->location->id ?? 'NULL') . ',id',
            'importance' => 'nullable|integer',
            'county_id' => 'required|exists:counties,id',
            'type' => 'nullable|string|max:100'
        ];
    }
}
