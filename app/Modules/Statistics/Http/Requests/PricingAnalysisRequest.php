<?php

namespace App\Modules\Statistics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PricingAnalysisRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'location_id' => 'required|integer|exists:locations,id',
            'category_id' => 'required|integer|exists:categories,id',
            'attributes' => 'nullable|array',
            'attributes.*' => 'string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'location_id.required' => 'Location is required for pricing analysis',
            'location_id.exists' => 'The selected location does not exist',
            'category_id.required' => 'Property category is required for pricing analysis',
            'category_id.exists' => 'The selected category does not exist',
            'attributes.array' => 'Attributes must be provided as an array',
        ];
    }
}