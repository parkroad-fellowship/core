<?php

namespace App\Rules\AllocationEntry;

use App\Enums\PRFMissionStatus;
use App\Enums\PRFMorphType;
use App\Models\AccountingEvent;
use App\Models\Mission;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class LockedByAccountingEvent implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $accountingEvent = AccountingEvent::query()
            ->where('ulid', $value)
            ->with('accountingEventable')
            ->first();

        if (! $accountingEvent) {
            return;
        }

        // Only apply this check to mission-related accounting events
        if ($accountingEvent->accounting_eventable_type !== PRFMorphType::MISSION->value) {
            return;
        }

        $mission = $accountingEvent->accountingEventable;

        if (! $mission instanceof Mission) {
            return;
        }

        if (in_array($mission->status, [
            PRFMissionStatus::SERVICED->value,
            PRFMissionStatus::CANCELLED->value,
            PRFMissionStatus::POSTPONED->value,
        ])) {
            $fail('This allocation entry is locked for updates');
        }
    }
}
