<?php

namespace App\Rules\EventSubscription;

use App\Models\EventSubscription;
use App\Models\Member;
use App\Models\PRFEvent;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Unique implements ValidationRule
{
    public function __construct(
        public string $prfEventUlid,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = EventSubscription::query()
            ->where([
                'member_id' => Member::query()
                    ->where('ulid', $value)
                    ->limit(1)
                    ->select('id'),
                'prf_event_id' => PRFEvent::query()
                    ->where('ulid', $this->prfEventUlid)
                    ->limit(1)
                    ->select('id'),
            ])
            ->exists();

        if ($exists) {
            $fail('You are already subscribed for this event.');
        }
    }
}
