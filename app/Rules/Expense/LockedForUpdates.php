<?php

namespace App\Rules\Expense;

use App\Enums\PRFMissionStatus;
use App\Enums\PRFMorphType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class LockedForUpdates implements ValidationRule
{
    public function __construct(
        public int $expenseableType,
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
        if ($this->expenseableType !== PRFMorphType::MISSION_EXPENSE->value) {
            return;
        }

        $expenseable = PRFMorphType::fromValue($this->expenseableType)->getModel()::query()
            ->where('ulid', $value)
            ->with('mission')
            ->firstOrFail();

        if (in_array($expenseable->mission->status, [
            PRFMissionStatus::SERVICED->value,
            PRFMissionStatus::CANCELLED->value,
            PRFMissionStatus::POSTPONED->value,
        ])) {
            $fail('This mission expense is locked for updates');
        }
    }
}
