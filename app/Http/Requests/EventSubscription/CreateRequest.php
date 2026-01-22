<?php

namespace App\Http\Requests\EventSubscription;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Rules\EventSubscription\FutureOnly;
use App\Rules\EventSubscription\Unique;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_ulid' => [
                'required', 'exists:prf_events,ulid',
                new FutureOnly,
            ],
            'member_ulid' => [
                'required', 'exists:members,ulid',
                new Unique($this->input('event_ulid')),
            ],
            'number_of_attendees' => 'required|integer|min:1',
        ];
    }
}
