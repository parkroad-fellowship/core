<?php

namespace App\Rules\MissionExpense;

use App\Enums\PRFMissionStatus;
use App\Models\MissionExpense;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

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
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Only apply this check to missions
        $missionExpense = MissionExpense::query()
            ->where('ulid', $this->missionExpenseUlid)
            ->with('mission')
            ->firstOrFail();

        if (in_array($missionExpense->mission->status, [
            PRFMissionStatus::SERVICED->value,
            PRFMissionStatus::CANCELLED->value,
            PRFMissionStatus::POSTPONED->value,
        ])) {
            $fail('This mission expense is locked for updates');
        }
    }
}
