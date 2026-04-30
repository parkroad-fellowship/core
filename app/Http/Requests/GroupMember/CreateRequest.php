<?php

namespace App\Http\Requests\GroupMember;

use App\Models\GroupMember;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(GroupMember::permission('create'));
    }

    public function rules(): array
    {
        return [
            'group_ulid' => ['required', 'string', 'exists:groups,ulid'],
            'member_ulid' => ['required', 'string', 'exists:members,ulid'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
