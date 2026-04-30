<?php

namespace App\Http\Requests\MissionSubscription;

use App\Models\MissionSubscription;
use App\Rules\MissionSubscription\FutureOnly;
use App\Rules\MissionSubscription\Unique;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(MissionSubscription::permission('create'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mission_ulid' => [
                'required', 'exists:missions,ulid',
                new FutureOnly,
            ],
            'member_ulid' => [
                'required', 'exists:members,ulid',
                new Unique($this->input('mission_ulid')),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }
}
