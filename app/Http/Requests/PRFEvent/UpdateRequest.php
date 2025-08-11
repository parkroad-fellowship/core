<?php

namespace App\Http\Requests\PRFEvent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
        ];
    }
}
