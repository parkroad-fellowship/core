<?php

namespace App\Http\Requests\PrayerRequest;

use App\Models\PrayerRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(PrayerRequest::permission('create'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member_ulid' => 'required|exists:members,ulid',
            'title' => 'nullable|string|max:255',
            'description' => 'required|string|min:3',
        ];
    }
}
