<?php

namespace App\Http\Requests\Membership;

use App\Enums\PRFMembershipType;
use App\Models\Membership;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Membership::permission('create'));
    }

    public function rules(): array
    {
        return [
            'member_ulid' => ['required', 'string', 'exists:members,ulid'],
            'spiritual_year_ulid' => ['required', 'string', 'exists:spiritual_years,ulid'],
            'type' => ['required', 'integer', 'in:' . implode(',', PRFMembershipType::getElements())],
            'approved' => ['sometimes', 'boolean'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
