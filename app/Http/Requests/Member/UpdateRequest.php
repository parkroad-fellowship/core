<?php

namespace App\Http\Requests\Member;

use App\Enums\PRFGender;
use App\Enums\PRFMemberEmailMode;
use App\Helpers\Utils;
use App\Models\Member;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Member::permission('edit'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $member = Member::where('ulid', $this->route('ulid'))->first();

        return [
            // Personal
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone_number' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('members', 'phone_number')
                    ->ignore($member)
                    ->where(fn($query) => $query->where('tenant_id', $this->tenantKey())),
            ],
            'personal_email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('members', 'personal_email')
                    ->ignore($member)
                    ->where(fn($query) => $query->where('tenant_id', $this->tenantKey())),
                // In personal-email mode this address becomes the member's login.
                function (string $attribute, mixed $value, Closure $fail) use ($member): void {
                    if (Utils::memberEmailMode() !== PRFMemberEmailMode::PERSONAL || !is_string($value)) {
                        return;
                    }

                    $takenByAnotherUser = User::withTrashed()
                        ->where('email', strtolower($value))
                        ->when($member?->user_id, fn($query, $userId) => $query->whereKeyNot($userId))
                        ->exists();

                    if ($takenByAnotherUser) {
                        $fail('This email already belongs to another account.');
                    }
                },
            ],
            'postal_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'residence' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string'],
            'linked_in_url' => ['sometimes', 'nullable', 'url', 'max:255'],

            // Spiritual/church
            'year_of_salvation' => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'church_volunteer' => ['sometimes', 'boolean'],
            'pastor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'church_ulid' => ['sometimes', 'nullable', 'string', 'exists:churches,ulid'],

            // Professional
            'profession_ulid' => ['sometimes', 'nullable', 'string', 'exists:professions,ulid'],
            'profession_institution' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profession_location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profession_contact' => ['sometimes', 'nullable', 'string', 'max:255'],

            // Demographics
            'gender' => ['sometimes', 'required', 'integer', Rule::in(PRFGender::getElements())],
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

    private function tenantKey(): string
    {
        return (string) tenancy()->tenant?->getTenantKey();
    }
}
