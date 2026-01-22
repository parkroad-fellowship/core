<?php

namespace App\Http\Requests\Requisition;

use App\Rules\Requisition\ApproveOnce;
use App\Rules\Requisition\PreventRejectedApproval;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApproveRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'approved_by_ulid' => [
                'required',
                'ulid',
                'exists:members,ulid',
                new ApproveOnce(ulid: $this->route('ulid')),
                new PreventRejectedApproval(ulid: $this->route('ulid')),
            ],
            'approval_notes' => 'sometimes|string',
        ];
    }
}
