<?php

namespace App\Http\Requests\Member;

use App\Enums\PRFGender;
use App\Models\Member;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Member::permission('create'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Personal
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('members', 'phone_number')],
            'personal_email' => ['required', 'email', 'max:255', Rule::unique('members', 'personal_email')],
            'postal_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'residence' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string'],
            'linked_in_url' => ['sometimes', 'nullable', 'url', 'max:255'],

            // Spiritual/church
            'year_of_salvation' => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:'.date('Y')],
            'church_volunteer' => ['sometimes', 'boolean'],
            'pastor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'church_ulid' => ['sometimes', 'nullable', 'string', 'exists:churches,ulid'],

            // Professional
            'profession_ulid' => ['sometimes', 'nullable', 'string', 'exists:professions,ulid'],
            'profession_institution' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profession_location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profession_contact' => ['sometimes', 'nullable', 'string', 'max:255'],

            // Demographics
            'gender' => ['sometimes', 'nullable', 'integer', Rule::in(PRFGender::getElements())],
            'marital_status_ulid' => ['sometimes', 'nullable', 'string', 'exists:marital_statuses,ulid'],

            // Relationships
            'department_ulids' => ['sometimes', 'array'],
            'department_ulids.*' => ['required', 'string', 'exists:departments,ulid'],
            'gift_ulids' => ['sometimes', 'array'],
            'gift_ulids.*' => ['required', 'string', 'exists:gifts,ulid'],
            'memberships' => ['sometimes', 'array'],
            'memberships.*.spiritual_year_ulid' => ['required', 'string', 'exists:spiritual_years,ulid'],
            'memberships.*.type' => ['required', 'string', 'max:255'],
            'memberships.*.approved' => ['sometimes', 'boolean'],
            'memberships.*.amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
