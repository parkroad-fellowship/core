<?php

namespace App\Http\Requests\Mission;

use App\Models\Mission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Mission::permission('create'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'school_term_ulid' => 'required|ulid|exists:school_terms,ulid',
            'mission_type_ulid' => 'required|ulid|exists:mission_types,ulid',
            'school_ulid' => 'required|ulid|exists:schools,ulid',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'theme' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'sometimes|integer',
            'mission_prep_notes' => 'nullable|string',
            'dressing_recommendations' => 'nullable|string',
            'activity_recommendations' => 'nullable|string',
            'whats_app_link' => 'nullable|string|max:500',
            'weather_recommendations' => 'nullable|array',
        ];
    }
}
