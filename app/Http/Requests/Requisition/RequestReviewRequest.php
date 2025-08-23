<?php

namespace App\Http\Requests\Requisition;

use App\Rules\Requisition\RequirePaymentInstruction;
use Illuminate\Foundation\Http\FormRequest;

class RequestReviewRequest extends FormRequest
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
            'appointed_approver_ulid' => [
                'required',
                'string',
                'exists:members,ulid',
                new RequirePaymentInstruction(ulid: $this->route('ulid')),
            ],
        ];
    }
}
