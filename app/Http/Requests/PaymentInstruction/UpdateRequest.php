<?php

namespace App\Http\Requests\PaymentInstruction;

use App\Models\PaymentInstruction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(PaymentInstruction::permission('edit'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'requisition_ulid' => 'sometimes|required|string|exists:requisitions,ulid',
            'payment_method' => 'sometimes|required|integer',
            'recipient_name' => 'sometimes|required|string|max:255',
            'reference' => 'nullable|string|max:255',
            'amount' => 'sometimes|required|integer|min:0',
            'mpesa_phone_number' => 'nullable|integer',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|integer',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
            'bank_swift_code' => 'nullable|string|max:255',
            'paybill_number' => 'nullable|integer',
            'paybill_account_number' => 'nullable|string|max:255',
            'till_number' => 'nullable|integer',
        ];
    }
}
