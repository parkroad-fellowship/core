<?php

namespace App\Http\Requests\BudgetEstimateEntry;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'budget_estimate_ulid' => ['required', 'string', 'exists:budget_estimates,ulid'],
            'expense_category_ulid' => ['required', 'string', 'exists:expense_categories,ulid'],
            'item_name' => ['required', 'string', 'max:255'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:1'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
