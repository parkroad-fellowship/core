<?php

namespace App\Http\Requests\AllocationEntry;

use App\Models\AllocationEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddTokenRequest extends FormRequest
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
            'accounting_event_ulid' => 'required|exists:accounting_events,ulid',
            'member_ulid' => 'required|exists:members,ulid',
            'entry_type' => 'required|numeric',
            'unit_cost' => 'required|integer',
            'confirmation_message' => 'required|string',
            'narration' => 'required|string',
        ];
    }
}
