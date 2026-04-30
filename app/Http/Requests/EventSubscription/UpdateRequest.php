<?php

namespace App\Http\Requests\EventSubscription;

use App\Models\EventSubscription;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(EventSubscription::permission('edit'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_ulid' => 'exists:prf_events,ulid',
            'member_ulid' => 'exists:members,ulid',
            'number_of_attendees' => 'required|integer|min:1',
        ];
    }
}
