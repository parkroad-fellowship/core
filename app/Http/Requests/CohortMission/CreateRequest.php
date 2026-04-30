<?php

namespace App\Http\Requests\CohortMission;

use App\Models\CohortMission;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(CohortMission::permission('create'));
    }

    public function rules(): array
    {
        return [
            'cohort_ulid' => ['required', 'string', 'exists:cohorts,ulid'],
            'mission_ulid' => ['required', 'string', 'exists:missions,ulid'],
        ];
    }
}
