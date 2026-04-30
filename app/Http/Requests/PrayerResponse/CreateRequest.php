<?php

namespace App\Http\Requests\PrayerResponse;

use App\Models\PrayerResponse;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(PrayerResponse::permission('create'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prayer_prompt_ulid' => [
                'required',
                'exists:prayer_prompts,ulid',
            ],
            'member_ulid' => [
                'required',
                'exists:members,ulid',
            ],
        ];
    }
}
