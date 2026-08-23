<?php

namespace App\Http\Requests\BudgetEstimate;

use App\Models\BudgetEstimate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(BudgetEstimate::permission('edit'));
    }

    public function rules(): array
    {
        return [
            'mission_type_ulid' => ['sometimes', 'string', 'exists:mission_types,ulid'],
            'grand_total' => ['sometimes', 'numeric', 'min:0'],
            'baseline_people' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
