<?php

namespace App\Http\Requests\LedgerCategory;

use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFResponsibleDesk;
use App\Models\LedgerCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(LedgerCategory::permission('create')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ledger_categories', 'name')->where('tenant_id', tenant('id'))->withoutTrashed(),
            ],
            'kind' => ['required', 'integer', Rule::in(PRFLedgerCategoryKind::getElements())],
            'responsible_desk' => ['nullable', 'integer', Rule::in(PRFResponsibleDesk::getElements())],
            'statement_line' => ['nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
