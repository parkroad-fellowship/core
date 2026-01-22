<?php

namespace App\Rules\MissionSubscription;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionSubscription;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Unique implements ValidationRule
{
    public function __construct(
        public string $missionUlid,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $exists = MissionSubscription::query()
            ->where([
                'member_id' => Member::query()
                    ->where('ulid', $value)
                    ->limit(1)
                    ->select('id'),
                'mission_id' => Mission::query()
                    ->where('ulid', $this->missionUlid)
                    ->limit(1)
                    ->select('id'),
            ])
            ->whereIn('status', [PRFMissionSubscriptionStatus::PENDING, PRFMissionSubscriptionStatus::APPROVED])
            ->exists();

        if ($exists) {
            $fail('You are already subscribed for this mission');
        }
    }
}
