<?php

namespace App\Http\Requests\Requisition;

use App\Models\Requisition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(Requisition::permission('create'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member_ulid' => 'required|ulid|exists:members,ulid',
            'accounting_event_ulid' => 'required|ulid|exists:accounting_events,ulid',
            'requisition_date' => 'required|date',
            'responsible_desk' => 'required|integer',
            'remarks' => 'required|string',
        ];
    }
}
