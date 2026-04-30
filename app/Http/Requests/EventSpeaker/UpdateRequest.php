<?php

namespace App\Http\Requests\EventSpeaker;

use App\Models\EventSpeaker;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(EventSpeaker::permission('edit'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prf_event_ulid' => ['sometimes', 'string', 'exists:prf_events,ulid'],
            'speaker_ulid' => ['sometimes', 'string', 'exists:speakers,ulid'],
            'topic' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'comments' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
