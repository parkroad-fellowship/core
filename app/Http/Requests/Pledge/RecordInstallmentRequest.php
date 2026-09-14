<?php

namespace App\Http\Requests\Pledge;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request used when the Treasurer (or an internal client) records a
 * follow-through installment for a pledge.
 */
class RecordInstallmentRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0',
            'fulfilled_on' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}
