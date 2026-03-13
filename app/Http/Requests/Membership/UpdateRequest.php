<?php

namespace App\Http\Requests\Membership;

use App\Models\Membership;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(Membership::permission('edit'));
    }

    public function rules(): array
    {
        return [
            'member_ulid' => ['sometimes', 'string', 'exists:members,ulid'],
            'spiritual_year_ulid' => ['sometimes', 'string', 'exists:spiritual_years,ulid'],
            'type' => ['sometimes', 'string', 'max:255'],
            'approved' => ['sometimes', 'boolean'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
