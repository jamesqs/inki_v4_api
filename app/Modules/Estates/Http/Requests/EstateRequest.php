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
            // Define your validation rules here
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:estates,slug,' . ($this->estate->id ?? 'null') . ',id',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'location_id' => 'required|exists:locations,id',
            'category_id' => 'required|exists:categories,id'
        ];
    }


}
