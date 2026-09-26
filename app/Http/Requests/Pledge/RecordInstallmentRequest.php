<?php

namespace App\Http\Requests\Pledge;

use App\Enums\PRFPledgeInstallmentMethod;
use App\Models\Pledge;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request used when the Treasurer (or an internal client) records a
 * follow-through installment for a pledge.
 */
class RecordInstallmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(Pledge::permission('edit')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'fulfilled_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'method' => [
                'required',
                'integer',
                Rule::in(array_map(
                    fn(PRFPledgeInstallmentMethod $method) => $method->value,
                    PRFPledgeInstallmentMethod::offline(),
                )),
            ],
            'financial_account_ulid' => ['required', 'string', 'exists:financial_accounts,ulid'],
            'reference' => ['nullable', 'string', 'max:255'],
            'send_receipt' => ['sometimes', 'boolean'],
        ];
    }
}
