<?php

namespace App\Rules\Requisition;

use App\Models\Requisition;
use App\Models\RequisitionItem;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class RequireLineItem implements ValidationRule
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
        $isMissingLineItems = RequisitionItem::query()
            ->where([
                'requisition_id' => Requisition::query()
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
