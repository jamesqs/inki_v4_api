<?php

namespace App\Modules\Attributes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttributeRequest extends FormRequest
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
        $rules = [
            'slug' => 'sometimes|string|max:255',
            'type' => 'required|string|in:string,number,boolean,select,checkbox,multiple',
            'options' => 'nullable|array',
            'options.*' => 'string|max:255'
        ];

        // Handle name validation differently for create vs update
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $attribute = $this->route('attribute'); // Get the attribute from the route
            $rules['name'] = ['required', 'string', 'max:255', Rule::unique('attributes', 'name')->ignore($attribute->id)];
        } else {
            // For create requests
            $rules['name'] = 'required|string|max:255|unique:attributes,name';
        }

        return $rules;
    }
}
