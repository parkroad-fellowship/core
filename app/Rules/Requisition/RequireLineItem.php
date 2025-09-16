<?php

namespace App\Rules\Requisition;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RequireLineItem implements ValidationRule
{
    public function __construct(
        public string $ulid
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isMissingLineItems = \App\Models\RequisitionItem::query()
            ->where([
                'requisition_id' => \App\Models\Requisition::query()
                    ->where('ulid', $this->ulid)
                    ->select('id')
                    ->limit(1),
            ])
            ->doesntExist();

        if ($isMissingLineItems) {
            $fail('A requisition must have at least one line item.');
        }
    }
}
