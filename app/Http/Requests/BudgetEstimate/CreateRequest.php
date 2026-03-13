<?php

namespace App\Http\Requests\BudgetEstimate;

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
            'budget_estimatable_ulid' => ['required', 'string'],
            'budget_estimatable_type' => ['required', 'string'],
            'grand_total' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
