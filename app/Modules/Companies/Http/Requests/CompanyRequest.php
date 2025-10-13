<?php

namespace App\Modules\Companies\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'county' => 'required|string|max:100',
            'zip' => 'required|string|max:20',
            'website' => 'nullable|url|max:255',
        ];

        // For update requests, we need to ignore the current record in unique validation
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $company = $this->route('company'); // Get the company from the route

            $rules['phone'] = ['required', 'string', 'max:20', Rule::unique('companies', 'phone')->ignore($company->id)];
            $rules['email'] = ['required', 'email', 'max:255', Rule::unique('companies', 'email')->ignore($company->id)];
            $rules['vat_number'] = ['required', 'string', 'max:50', Rule::unique('companies', 'vat_number')->ignore($company->id)];
            $rules['registration_number'] = ['nullable', 'string', 'max:50', Rule::unique('companies', 'registration_number')->ignore($company->id)];
        } else {
            // For create requests
            $rules['phone'] = 'required|string|max:20|unique:companies,phone';
            $rules['email'] = 'required|email|max:255|unique:companies,email';
            $rules['vat_number'] = 'required|string|max:50|unique:companies,vat_number';
            $rules['registration_number'] = 'nullable|string|max:50|unique:companies,registration_number';
        }

        return $rules;

    }
}
