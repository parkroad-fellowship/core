<?php

namespace App\Http\Requests\Soul;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'mission_ulid' => 'required|exists:missions,ulid',
            'class_group_ulid' => 'required|exists:class_groups,ulid',
            'full_name' => 'required|string',
            'admission_number' => 'nullable|string',
        ];
    }
}
