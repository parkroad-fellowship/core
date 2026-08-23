<?php

namespace App\Http\Requests\BudgetEstimate;

use App\Models\BudgetEstimate;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(BudgetEstimate::permission('create'));
    }

    public function rules(): array
    {
        return [
            'budget_estimatable_ulid' => ['required', 'string'],
            'budget_estimatable_type' => ['required', 'string'],
            'mission_type_ulid' => ['required', 'string', 'exists:mission_types,ulid'],
            'grand_total' => ['required', 'numeric', 'min:0'],
            'baseline_people' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
