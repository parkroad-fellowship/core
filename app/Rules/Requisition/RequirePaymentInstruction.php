<?php

namespace App\Rules\Requisition;

use App\Models\PaymentInstruction;
use App\Models\Requisition;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class RequirePaymentInstruction implements ValidationRule
{
    public function __construct(
        public string $ulid
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $doesntExist = PaymentInstruction::query()
            ->where([
                'requisition_id' => Requisition::query()
                    ->where('ulid', $this->ulid)
                    ->select('id')
                    ->limit(1),
            ])
            ->doesntExist();

        if ($doesntExist) {
            $fail('You must provide a payment instruction for this requisition.');
        }

    }
}
