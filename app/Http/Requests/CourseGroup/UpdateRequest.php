<?php

namespace App\Http\Requests\CourseGroup;

use App\Models\CourseGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(CourseGroup::permission('edit'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'group_ulid' => ['sometimes', 'string', 'exists:groups,ulid'],
            'course_ulid' => ['sometimes', 'string', 'exists:courses,ulid'],
            'start_date' => ['sometimes', 'date'],
        ];
    }
}
