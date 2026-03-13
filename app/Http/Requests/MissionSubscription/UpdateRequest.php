<?php

namespace App\Http\Requests\MissionSubscription;

use App\Models\MissionSubscription;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(MissionSubscription::permission('edit'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mission_ulid' => 'exists:missions,ulid',
            'member_ulid' => 'exists:members,ulid',
            'status' => [
                'required',
            ],
        ];
    }
}
