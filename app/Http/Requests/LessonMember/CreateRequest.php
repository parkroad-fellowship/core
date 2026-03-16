<?php

namespace App\Http\Requests\LessonMember;

use App\Models\LessonMember;
use App\Rules\LessonMember\Unique;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(LessonMember::permission('create'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lesson_ulid' => ['required', 'string', 'exists:lessons,ulid'],
            'module_ulid' => ['required', 'string', 'exists:modules,ulid'],
            'course_ulid' => ['required', 'string', 'exists:courses,ulid'],
            'member_ulid' => [
                'required', 'string', 'exists:members,ulid',
                new Unique(
                    $this->input('lesson_ulid'),
                    $this->input('module_ulid'),
                    $this->input('course_ulid'),
                ),
            ],
            'completion_status' => ['required', 'numeric'],
        ];
    }
}
