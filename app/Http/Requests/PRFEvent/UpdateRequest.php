<?php

namespace App\Http\Requests\PRFEvent;

use App\Models\PRFEvent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(PRFEvent::permission('edit'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'start_time' => 'sometimes|string',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'end_time' => 'sometimes|string',
            'venue' => 'sometimes|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacity' => 'sometimes|integer|min:0',
            'status' => 'sometimes|integer',
            'responsible_desk' => 'sometimes|integer',
            'event_type' => 'sometimes|integer',
            'dressing_recommendations' => 'nullable|string',
            'weather_recommendations' => 'nullable|string',
            'participant_member_ulids' => 'nullable|array',
            'participant_member_ulids.*' => 'ulid|distinct|exists:members,ulid',
        ];
    }
}
