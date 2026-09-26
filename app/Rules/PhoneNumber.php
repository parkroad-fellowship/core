<?php

namespace App\Rules;

use App\Helpers\Utils;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A phone number we can text: local ("0712 345 678") or international ("+254712345678").
 */
class PhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (filled($value) && Utils::toE164(is_scalar($value) ? (string) $value : null) === null) {
            $fail('The :attribute is not a valid phone number.');
        }
    }
}
