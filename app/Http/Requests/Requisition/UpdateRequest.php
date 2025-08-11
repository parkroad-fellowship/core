<?php

namespace App\Http\Requests\Requisition;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'accounting_event_ulid' => 'sometimes|required|string|exists:accounting_events,ulid',
            'requisition_date' => 'sometimes|required|date',
            'responsible_desk' => 'sometimes|required|integer',
            'remarks' => 'nullable|string',
            'total_amount' => 'sometimes|required|integer|min:0',
            'approval_status' => 'sometimes|required|string',
            'approval_notes' => 'nullable|string',
        ];
    }
}
