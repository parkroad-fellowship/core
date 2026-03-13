<?php

namespace App\Http\Requests\CohortLetter;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'cohort_ulid' => ['required', 'string', 'exists:cohorts,ulid'],
            'letter_ulid' => ['required', 'string', 'exists:letters,ulid'],
        ];
    }
}
