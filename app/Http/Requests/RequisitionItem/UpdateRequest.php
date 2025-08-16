<?php

namespace App\Http\Requests\RequisitionItem;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'requisition_ulid' => 'sometimes|required|string|exists:requisitions,ulid',
            'expense_category_ulid' => 'sometimes|required|string|exists:expense_categories,ulid',
            'item_name' => 'sometimes|required|string|max:255',
            'unit_price' => 'sometimes|required|integer|min:0',
            'quantity' => 'sometimes|required|integer|min:1',
        ];
    }
}
