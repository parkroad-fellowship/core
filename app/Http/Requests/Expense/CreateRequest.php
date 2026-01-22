<?php

namespace App\Http\Requests\Expense;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Rules\Expense\LockedForUpdates;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'expense_category_ulid' => 'required|exists:expense_categories,ulid',
            'member_ulid' => 'required|exists:members,ulid',
            'charge_type' => 'required|numeric',
            'charge' => 'required|integer',
            'expenseable_ulid' => [
                'required',
                new LockedForUpdates($this->input('expenseable_type')),
            ],
            'expenseable_type' => 'required|numeric',
            'unit_cost' => 'required|integer',
            'confirmation_message' => 'required|string',
            'quantity' => 'required|integer',
            'narration' => 'required|string',
        ];
    }
}
