<?php

namespace App\Rules\MissionSubscription;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FutureOnly implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $mission = \App\Models\Mission::query()
            ->where('ulid', $value)
            ->first();

        if ($mission && $mission->start_date->isPast()) {
            $fail('You can only subscribe to future missions');
        }
    }
}
