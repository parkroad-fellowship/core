<?php

namespace App\Http\Requests\School;

use App\Models\School;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMissionDefaultsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(School::permission('edit'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'types' => ['required', 'array', 'min:1'],
            'types.*.mission_type_ulid' => ['required', 'string', 'exists:mission_types,ulid'],
            'types.*.start_time' => ['nullable', 'date_format:H:i'],
            'types.*.end_time' => ['nullable', 'date_format:H:i'],
            'types.*.capacity' => ['nullable', 'integer', 'min:1', 'max:200'],
            'default_mission_type_ulid' => ['nullable', 'string', 'exists:mission_types,ulid'],
        ];
    }
}
