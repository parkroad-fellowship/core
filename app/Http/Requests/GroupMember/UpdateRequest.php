<?php

namespace App\Http\Requests\GroupMember;

use App\Models\GroupMember;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(GroupMember::permission('edit'));
    }

    public function rules(): array
    {
        return [
            'group_ulid' => ['sometimes', 'string', 'exists:groups,ulid'],
            'member_ulid' => ['sometimes', 'string', 'exists:members,ulid'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
