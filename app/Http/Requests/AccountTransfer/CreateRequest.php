<?php

namespace App\Http\Requests\AccountTransfer;

use App\Models\AccountTransfer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccountTransfer::permission('create')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from_financial_account_ulid' => ['required', 'string', 'exists:financial_accounts,ulid'],
            'to_financial_account_ulid' => [
                'required',
                'string',
                'exists:financial_accounts,ulid',
                'different:from_financial_account_ulid',
            ],
            'amount' => ['required', 'integer', 'min:1'],
            'charge' => ['sometimes', 'integer', 'min:0'],
            'transferred_on' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
