<?php

namespace App\Modules\Categories\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:categories,name,' . ($this->category->id ?? 'null') . ',id',
            'slug' => 'optional|string|max:255|unique:categories,slug,' . ($this->category->id ?? 'null') . ',id',
            'description' => 'nullable|string'
        ];
    }
}
