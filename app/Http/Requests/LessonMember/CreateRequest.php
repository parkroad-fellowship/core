<?php

namespace App\Http\Requests\LessonMember;

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
            'lesson_ulid' => ['required', 'string', 'exists:lessons,ulid'],
            'module_ulid' => ['required', 'string', 'exists:modules,ulid'],
            'course_ulid' => ['required', 'string', 'exists:courses,ulid'],
            'member_ulid' => ['required', 'string', 'exists:members,ulid'],
            'completion_status' => ['required', 'numeric'],
        ];
    }
}
