<?php

namespace App\Rules\EventSubscription;

use App\Models\PRFEvent;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class FutureOnly implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $prfEvent = PRFEvent::query()
            ->where('ulid', $value)
            ->first();

        if ($prfEvent && $prfEvent->start_date->isPast()) {
            $fail('You can only subscribe to future events');
        }
    }
}
