<?php

namespace App\Http\Requests\RequisitionItem;

use App\Models\RequisitionItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(RequisitionItem::permission('create'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'requisition_ulid' => 'required|string|exists:requisitions,ulid',
            'expense_category_ulid' => 'required|string|exists:expense_categories,ulid',
            'item_name' => 'required|string|max:255',
            'narration' => 'nullable|string',
            'unit_price' => 'required|integer|min:0',
            'quantity' => 'required|integer|min:1',
        ];
    }
}
