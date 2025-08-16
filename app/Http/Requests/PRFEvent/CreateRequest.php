<?php

namespace App\Http\Requests\PRFEvent;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'end_date' => 'required|date|after_or_equal:start_date',
            'end_time' => 'required|string',
            'venue' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacity' => 'required|integer|min:0',
            'status' => 'required|integer',
            'responsible_desk' => 'required|integer',
            'event_type' => 'required|integer',
            'dressing_recommendations' => 'nullable|string',
            'weather_recommendations' => 'nullable|string',
        ];
    }
}
