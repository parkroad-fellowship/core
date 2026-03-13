<?php

namespace App\Http\Requests\Payment;

use App\Models\Payment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(Payment::permission('create'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_type_ulid' => 'required|exists:payment_types,ulid',
            'member_ulid' => 'required|exists:members,ulid',
            'amount' => 'required|integer',
        ];
    }
}
