<?php

namespace App\Modules\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlogPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert empty string or 0 to null for category_id
        if ($this->has('category_id') && ($this->category_id === '' || $this->category_id === 0 || $this->category_id === '0')) {
            $this->merge(['category_id' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'title' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug,' . $this->route('blogPost')?->id,
            'content' => $isUpdate ? 'sometimes|string' : 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'category_id' => 'nullable|integer|exists:categories,id',
            'user_id' => $isUpdate ? 'sometimes|exists:users,id' : 'required|exists:users,id',
            'published' => 'sometimes|boolean',
            'featured' => 'sometimes|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'published_at' => 'nullable|date',
            'media_ids' => 'nullable|array',
            'media_ids.*' => 'exists:media,id'
        ];
    }
}
