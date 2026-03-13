<?php

namespace App\Http\Requests\BudgetEstimateEntry;

use App\Models\BudgetEstimateEntry;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(BudgetEstimateEntry::permission('edit'));
    }

    public function rules(): array
    {
        return [
            'expense_category_ulid' => ['sometimes', 'string', 'exists:expense_categories,ulid'],
            'item_name' => ['sometimes', 'string', 'max:255'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'total_price' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
