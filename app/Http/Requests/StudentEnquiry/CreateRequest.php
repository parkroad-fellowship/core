<?php

namespace App\Http\Requests\StudentEnquiry;

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
            'mission_faq_ulid' => 'nullable|exists:mission_faqs,ulid',
            'student_ulid' => 'required|exists:students,ulid',
            'content' => 'required|string',
        ];
    }
}
