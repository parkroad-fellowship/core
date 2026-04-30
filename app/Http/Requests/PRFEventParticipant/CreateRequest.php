<?php

namespace App\Http\Requests\PRFEventParticipant;

use App\Models\PRFEventParticipant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(PRFEventParticipant::permission('create'));
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
            'member_ulid' => ['required', 'string', 'exists:members,ulid'],
        ];
    }
}
