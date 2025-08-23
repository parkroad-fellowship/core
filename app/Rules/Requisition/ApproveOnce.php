<?php

namespace App\Rules\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Models\Requisition;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ApproveOnce implements ValidationRule
{
    public function __construct(
        public string $ulid,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Requisition::query()
            ->where([
                'ulid' => $this->ulid,
                'status' => PRFApprovalStatus::APPROVED->value,
            ])
            ->exists();

        if ($exists) {
            $fail('You cannot approve an already approved requisitions');
        }
    }
}
