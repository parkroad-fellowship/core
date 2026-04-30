<?php

namespace App\Http\Requests\CourseMember;

use App\Models\CourseMember;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(CourseMember::permission('create'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'course_ulid' => ['required', 'string', 'exists:courses,ulid'],
            'member_ulid' => ['required', 'string', 'exists:members,ulid'],
        ];
    }
}
