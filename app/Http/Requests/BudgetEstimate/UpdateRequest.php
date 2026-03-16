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
            'grand_total' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
