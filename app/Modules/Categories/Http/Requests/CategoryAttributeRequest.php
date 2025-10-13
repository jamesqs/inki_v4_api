<?php

namespace App\Modules\Categories\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryAttributeRequest extends FormRequest
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
            'attributes' => 'required|array',
            'attributes.*.attribute_id' => 'required|integer|exists:attributes,id',
            'attributes.*.required' => 'boolean',
            'attributes.*.order' => 'integer|min:0',
        ];
    }
}
