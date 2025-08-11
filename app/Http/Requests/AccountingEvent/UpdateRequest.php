<?php

namespace App\Http\Requests\AccountingEvent;

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
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'status' => 'required|string|max:50',
            'responsible_desk' => 'required|integer',
            'accounting_eventable_ulid' => 'required|ulid',
            'accounting_eventable_type' => 'required|integer',
        ];
    }
}
