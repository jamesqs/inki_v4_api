<?php

namespace App\Modules\Estates\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EstateRequest extends FormRequest
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
            // Basic info
            'status' => 'required|in:draft,pending_review,approved,rejected,archived',
            'listing_type' => 'required|in:sale,rent',
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|min:50',

            // Price
            'price' => 'required|numeric|min:0',
            'price_type' => 'required|in:fixed,auction',
            'currency' => 'required|in:HUF,EUR',

            // Location
            'location_id' => 'required|exists:locations,id',

            // Address
            'address' => 'required|array',
            'address.zip' => 'required|string',
            'address.street' => 'required|string',
            'address.house_number' => 'nullable|string',
            'address.plot_number' => 'nullable|string',
            'address.display_mode' => 'required|in:exact,street,street_only,city_only',
            'address.coordinates' => 'nullable|array',
            'address.coordinates.lat' => 'nullable|numeric',
            'address.coordinates.lng' => 'nullable|numeric',

            // Attributes
            'attributes' => 'nullable|array',

            // Photos
            'photos' => 'nullable|array',

            // Floor plan data - accept any valid array structure
            'floor_plan_data' => 'nullable|array',

            // Optional fields for backward compatibility
            'slug' => 'nullable|string|max:255|unique:estates,slug,' . ($this->estate->id ?? 'null') . ',id',
            'name' => 'nullable|string|max:255',  // Can use 'title' or 'name'
        ];
    }


}
