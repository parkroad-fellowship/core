<?php

namespace App\Http\Requests\AllocationEntry;

use App\Models\AllocationEntry;
use App\Rules\AllocationEntry\LockedByAccountingEvent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DeleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(AllocationEntry::permission('delete'));
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $allocationEntry = AllocationEntry::query()
            ->where('ulid', $this->route('ulid'))
            ->with('accountingEvent')
            ->first();

        $this->merge([
            'ulid' => $this->route('ulid'),
            'accounting_event_ulid' => $allocationEntry?->accountingEvent?->ulid,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ulid' => 'required|exists:allocation_entries,ulid',
            'accounting_event_ulid' => [
                'required',
                new LockedByAccountingEvent,
            ],
        ];
    }
}
