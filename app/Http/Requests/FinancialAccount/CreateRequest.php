<?php

namespace App\Http\Requests\FinancialAccount;

use App\Enums\PRFFinancialAccountType;
use App\Models\FinancialAccount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(FinancialAccount::permission('create')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('financial_accounts', 'name')->where('tenant_id', tenant('id'))->withoutTrashed(),
            ],
            'type' => ['required', 'integer', Rule::in(PRFFinancialAccountType::getElements())],
            'identifier' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
