<?php

namespace App\Http\Requests\MissionGroundSuggestion;

use App\Enums\PRFMissionGroundSuggestionStatus;
use App\Models\MissionGroundSuggestion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(MissionGroundSuggestion::permission('edit'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'contact_number' => 'required|string|max:255',
            'suggestor_ulid' => 'required|exists:members,ulid',
            'status' => 'required|in:'.implode(',', PRFMissionGroundSuggestionStatus::values()),
            'notes' => 'nullable|string',
        ];
    }
}
