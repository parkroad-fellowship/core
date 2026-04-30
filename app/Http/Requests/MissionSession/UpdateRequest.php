<?php

namespace App\Http\Requests\MissionSession;

use App\Models\MissionSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(MissionSession::permission('edit'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mission_ulid' => ['required', 'ulid', 'exists:missions,ulid'],
            'facilitator_ulid' => ['required', 'ulid', 'exists:members,ulid'],
            'speaker_ulid' => ['ulid', 'exists:members,ulid'],
            'class_group_ulid' => ['ulid', 'exists:class_groups,ulid'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
            'notes' => ['required', 'string'],
            'order' => ['required', 'integer'],
        ];
    }
}
