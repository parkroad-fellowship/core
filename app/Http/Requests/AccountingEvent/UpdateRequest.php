<?php

namespace App\Http\Requests\AccountingEvent;

use App\Enums\PRFAccountEventStatus;
use App\Enums\PRFReconciliationStatus;
use App\Models\AccountingEvent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(AccountingEvent::permission('edit'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'integer', 'in:' . implode(',', PRFAccountEventStatus::getElements())],
            'responsible_desk' => ['sometimes', 'required', 'integer'],
            'accounting_eventable_ulid' => ['sometimes', 'required', 'ulid'],
            'accounting_eventable_type' => ['required_with:accounting_eventable_ulid', 'integer'],
            'reconciliation_status' => ['sometimes', 'integer', Rule::in(PRFReconciliationStatus::getElements())],
            'reconciliation_remarks' => [
                Rule::requiredIf(
                    fn() => (
                        (int) $this->input('reconciliation_status') === PRFReconciliationStatus::NEEDS_ATTENTION->value
                    ),
                ),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
