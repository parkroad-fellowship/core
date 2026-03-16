<?php

namespace App\Http\Requests\CohortLetter;

use App\Models\CohortLetter;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(CohortLetter::permission('create'));
    }

    public function rules(): array
    {
        return [
            'cohort_ulid' => ['required', 'string', 'exists:cohorts,ulid'],
            'letter_ulid' => ['required', 'string', 'exists:letters,ulid'],
        ];
    }
}
