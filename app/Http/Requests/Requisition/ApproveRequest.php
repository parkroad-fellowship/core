<?php

namespace App\Http\Requests\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Models\Requisition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class ApproveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(Requisition::permission('approve'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'approval_notes' => ['sometimes', 'string'],
            // Optional: the account the treasurer paid it out of. Without it the disbursement is
            // booked later from the cashbook.
            'financial_account_ulid' => ['sometimes', 'nullable', 'string', 'exists:financial_accounts,ulid'],
            'charge' => ['sometimes', 'integer', 'min:0'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'paid_on' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $requisition = Requisition::query()->where('ulid', $this->route('ulid'))->first();

                if (!$requisition) {
                    return;
                }

                if ($requisition->approval_status === PRFApprovalStatus::APPROVED) {
                    $validator->errors()->add('ulid', 'You cannot approve an already approved requisition.');
                }

                if ($requisition->approval_status === PRFApprovalStatus::REJECTED) {
                    $validator->errors()->add('ulid', 'You cannot approve an already rejected requisition.');
                }

                if ($requisition->requested_by === Auth::user()?->member?->id) {
                    $validator->errors()->add('ulid', 'You cannot approve your own requisition.');
                }
            },
        ];
    }
}
