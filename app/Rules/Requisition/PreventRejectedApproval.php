<?php

namespace App\Rules\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Models\Requisition;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PreventRejectedApproval implements ValidationRule
{
    public function __construct(
        public string $ulid,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Requisition::query()
            ->where([
                'ulid' => $this->ulid,
                'approval_status' => PRFApprovalStatus::REJECTED->value,
            ])
            ->exists();

        if ($exists) {
            $fail('You cannot approve an already rejected requisition.');
        }
    }
}
