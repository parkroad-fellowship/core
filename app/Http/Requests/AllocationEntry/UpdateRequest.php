<?php

namespace App\Http\Requests\AllocationEntry;

use App\Models\AllocationEntry;
use App\Rules\AllocationEntry\LockedByAccountingEvent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(AllocationEntry::permission('edit'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'accounting_event_ulid' => [
                'required',
                'exists:accounting_events,ulid',
                new LockedByAccountingEvent,
            ],
            'expense_category_ulid' => 'required|exists:expense_categories,ulid',
            'member_ulid' => 'required|exists:members,ulid',
            'entry_type' => 'required|numeric',
            'charge_type' => 'required|numeric',
            'charge' => 'required|integer',
            'unit_cost' => 'required|integer',
            'confirmation_message' => 'required|string',
            'quantity' => 'required|integer',
            'narration' => 'required|string',
        ];
    }
}
