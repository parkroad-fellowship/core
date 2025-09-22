<?php

namespace App\Rules\MissionExpense;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LockedForUpdates implements ValidationRule
{
    public function __construct(
        public string $missionExpenseUlid,
    ) {
        //
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Only apply this check to missions
        $missionExpense = \App\Models\MissionExpense::query()
            ->where('ulid', $this->missionExpenseUlid)
            ->with('mission')
            ->firstOrFail();

        if (in_array($missionExpense->mission->status, [
            \App\Enums\PRFMissionStatus::SERVICED->value,
            \App\Enums\PRFMissionStatus::CANCELLED->value,
            \App\Enums\PRFMissionStatus::POSTPONED->value,
        ])) {
            $fail('This mission expense is locked for updates');
        }
    }
}
