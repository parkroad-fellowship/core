<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'expense_category_ulid' => 'required|exists:expense_categories,ulid',
            'member_ulid' => 'required|exists:members,ulid',
            'channel_type' => 'required|numeric',
            'charge_type' => 'required|numeric',
            'expenseable_ulid' => 'required',
            'expenseable_type' => 'required|numeric',
            'amount' => 'required|integer',
            'confirmation_message' => 'required|string',
        ];
    }
}
