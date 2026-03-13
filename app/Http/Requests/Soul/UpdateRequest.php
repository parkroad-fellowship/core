<?php

namespace App\Http\Requests\Soul;

use App\Enums\PRFSoulDecisionType;
use App\Models\Soul;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(Soul::permission('edit'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mission_ulid' => 'required|exists:missions,ulid',
            'class_group_ulid' => 'required|exists:class_groups,ulid',
            'full_name' => 'required|string',
            'admission_number' => 'nullable|string',
            'decision_type' => [
                'nullable',
                'integer',
                Rule::in(PRFSoulDecisionType::getValues()),
            ],
            'notes' => 'nullable|string',
        ];
    }
}
