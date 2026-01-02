<?php

namespace App\Http\Requests\SchoolContact;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'school_ulid' => 'required|exists:schools,ulid',
            'contact_type_ulid' => 'required|exists:contact_types,ulid',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:255',
            'is_active' => 'nullable|integer',
            'preferred_name' => 'nullable|string|max:255',
        ];
    }
}
