<?php

namespace App\Http\Requests\AllocationEntry;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'accounting_event_ulid' => 'required|exists:accounting_events,ulid',
            'allocation_ulid' => 'required|exists:allocations,ulid',
            'expense_category_ulid' => 'required|exists:expense_categories,ulid',
            'member_ulid' => 'required|exists:members,ulid',
            'charge_type' => 'required|numeric',
            'charge' => 'required|integer',
            'unit_cost' => 'required|integer',
            'confirmation_message' => 'required|string',
            'quantity' => 'required|integer',
            'narration' => 'required|string',
            'confirmation_message' => 'nullable|string',
        ];
    }
}
