<?php

namespace App\Http\Requests\PRFEvent;

use App\Models\PRFEvent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(PRFEvent::permission('create'));
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
            'description' => 'required|string',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'end_date' => 'required|date|after_or_equal:start_date',
            'end_time' => 'required|string|after_or_equal:start_time',
            'responsible_desk' => 'required|integer',
            'event_type' => 'required|integer',
            'participant_member_ulids' => 'nullable|array',
            'participant_member_ulids.*' => 'ulid|distinct|exists:members,ulid',
        ];
    }
}
