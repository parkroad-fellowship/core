<?php

namespace App\Http\Requests\Requisition;

use App\Models\Requisition;
use App\Rules\Requisition\RequireLineItem;
use App\Rules\Requisition\RequirePaymentInstruction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RequestReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(Requisition::permission('edit'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'appointed_approver_ulid' => [
                'required',
                'string',
                'exists:members,ulid',
                new RequirePaymentInstruction(ulid: $this->route('ulid')),
                new RequireLineItem(ulid: $this->route('ulid')),
            ],
        ];
    }
}
