<?php

namespace App\Http\Requests\LedgerEntry;

use App\Enums\PRFLedgerChannel;
use App\Enums\PRFLedgerFlow;
use App\Models\LedgerEntry;
use App\Rules\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(LedgerEntry::permission('create')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'financial_account_ulid' => ['required', 'string', 'exists:financial_accounts,ulid'],
            'ledger_category_ulid' => ['required', 'string', 'exists:ledger_categories,ulid'],
            // Only needed for transfer categories; every other kind has a natural direction.
            'flow' => ['nullable', 'integer', Rule::in(PRFLedgerFlow::getElements())],
            'channel' => ['nullable', 'integer', Rule::in(PRFLedgerChannel::getElements())],
            'amount' => ['required', 'integer', 'min:1'],
            'transacted_on' => ['required', 'date', 'before_or_equal:today'],
            'counterparty' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'reference' => ['nullable', 'string', 'max:255'],
            'member_ulid' => ['nullable', 'string', 'exists:members,ulid'],
            'giver_email' => ['nullable', 'email', 'max:255'],
            'giver_phone' => ['nullable', 'string', new PhoneNumber()],
            'accounting_event_ulid' => ['nullable', 'string', 'exists:accounting_events,ulid'],
            'pledge_ulid' => ['nullable', 'string', 'exists:pledges,ulid'],
            'membership_ulid' => ['nullable', 'string', 'exists:memberships,ulid'],
            'send_receipt' => ['sometimes', 'boolean'],
        ];
    }
}
