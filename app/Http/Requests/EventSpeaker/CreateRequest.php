<?php

namespace App\Http\Requests\EventSpeaker;

use App\Models\EventSpeaker;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(EventSpeaker::permission('create'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prf_event_ulid' => ['required', 'string', 'exists:prf_events,ulid'],
            'speaker_ulid' => ['required', 'string', 'exists:speakers,ulid'],
            'topic' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'comments' => ['nullable', 'string'],
        ];
    }
}
