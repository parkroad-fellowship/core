<?php

namespace App\Rules\Turnstile;

use App\Services\Turnstile\TurnstileService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidTurnstile implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === '' || strlen($value) > 2048) {
            $fail('Turnstile verification failed. Please try again.');

            return;
        }

        $result = app(TurnstileService::class)->verify($value, request()->ip());

        if (!$result['success']) {
            $fail('Turnstile verification failed. Please try again.');
        }
    }
}
