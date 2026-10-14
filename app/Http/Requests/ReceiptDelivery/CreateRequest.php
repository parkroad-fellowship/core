<?php

namespace App\Http\Requests\ReceiptDelivery;

use App\Enums\PRFReceiptChannel;
use App\Models\ReceiptDelivery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(ReceiptDelivery::permission('create')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ledger_entry_ulid' => [
                'required',
                'string',
                Rule::exists('ledger_entries', 'ulid')->whereNotNull('receipt_number'),
            ],
            'channel' => ['required', 'integer', Rule::in(PRFReceiptChannel::getElements())],
            // Defaults to the giver's email or phone on the receipt.
            'recipient' => ['nullable', 'string', 'max:255'],
        ];
    }
}
