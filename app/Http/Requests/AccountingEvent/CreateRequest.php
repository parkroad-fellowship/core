<?php

namespace App\Http\Requests\AccountingEvent;

use App\Models\AccountingEvent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(AccountingEvent::permission('create'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'status' => 'required|string|max:50',
            'responsible_desk' => 'required|integer',
            'accounting_eventable_ulid' => 'required|ulid',
            'accounting_eventable_type' => 'required|integer',
        ];
    }
}
