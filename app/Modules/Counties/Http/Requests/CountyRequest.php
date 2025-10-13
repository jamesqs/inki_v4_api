<?php

namespace App\Modules\Counties\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CountyRequest extends FormRequest
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
            'code' => 'sometimes|string|max:10|unique:counties,code,',
            'country_code' => 'required|string|max:10'

        ];
    }
}
