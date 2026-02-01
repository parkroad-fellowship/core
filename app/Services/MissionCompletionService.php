<?php

namespace App\Services;

use App\Enums\PRFEntryType;
use App\Enums\PRFMissionStatus;
use App\Models\Mission;

class MissionCompletionService
{
    /**
     * @return array{
     *     can_complete: bool,
     *     checks: array<string, array{
     *         passed: bool,
     *         required: bool,
     *         label: string,
     *         description: string,
     *         count: int|null
     *     }>,
     *     message: string|null
     * }
     */
    public function getCompletionChecklist(Mission $mission): array
    {
        $checks = [];

        $photoCount = $mission->getMedia(Mission::MISSION_PHOTOS)->count();
        $checks['photos'] = [
            'passed' => $photoCount >= 1,
            'required' => false,
            'label' => 'Mission Photos',
            'description' => $photoCount >= 1
                ? "{$photoCount} photo(s) uploaded"
                : 'At least 1 photo required',
            'count' => $photoCount,
        ];

        $noteCount = $mission->debriefNotes()->count();
        $checks['debrief_notes'] = [
            'passed' => $noteCount >= 1,
            'required' => true,
            'label' => 'Debrief Notes',
            'description' => $noteCount >= 1
                ? "{$noteCount} debrief note(s) recorded"
                : 'At least 1 debrief note required',
            'count' => $noteCount,
        ];

        $soulCount = $mission->souls()->count();
        $checks['souls'] = [
            'passed' => $soulCount >= 1,
            'required' => false,
            'label' => 'Souls / Students',
            'description' => $soulCount >= 1
                ? "{$soulCount} soul(s) recorded"
                : 'At least 1 soul/student record required',
            'count' => $soulCount,
        ];

        $accountingEvent = $mission->accountingEvent;
        if ($accountingEvent) {
            $credits = $accountingEvent->allocationEntries()
                ->where('entry_type', PRFEntryType::CREDIT->value)
                ->sum('amount');

            if ($credits > 0) {
                $debits = $accountingEvent->allocationEntries()
                    ->where('entry_type', PRFEntryType::DEBIT->value)
                    ->sum('amount');

                $hasExpenseEntries = $debits >= 0;

                $checks['finances'] = [
                    'passed' => $hasExpenseEntries,
                    'required' => true,
                    'label' => 'Financial Records',
                    'description' => $hasExpenseEntries
                        ? 'Expenses recorded (KES '.number_format($debits).' of '.number_format($credits).' spent)'
                        : 'Money was issued (KES '.number_format($credits).') - expense records required',
                    'count' => (int) $debits,
                ];
            }
        }

        $allRequiredPassed = collect($checks)
            ->filter(fn ($check) => $check['required'])
            ->every(fn ($check) => $check['passed']);

        $failedChecks = collect($checks)
            ->filter(fn ($check) => $check['required'] && ! $check['passed'])
            ->keys()
            ->map(fn ($key) => $checks[$key]['label'])
            ->toArray();

        $message = $allRequiredPassed
            ? 'All requirements met. Mission can be marked as completed.'
            : 'Please complete: '.implode(', ', $failedChecks);

        return [
            'can_complete' => $allRequiredPassed,
            'checks' => $checks,
            'message' => $message,
        ];
    }

    /**
     * Mark a mission as serviced if all checks pass.
     */
    public function completeMission(Mission $mission): bool
    {
        $checklist = $this->getCompletionChecklist($mission);

        if (! $checklist['can_complete']) {
            return false;
        }

        $mission->update(['status' => PRFMissionStatus::SERVICED->value]);

        return true;
    }

    /**
     * Check if a mission can bypass the checklist (e.g., already serviced).
     */
    public function canBypassChecklist(Mission $mission): bool
    {
        return $mission->status === PRFMissionStatus::SERVICED->value;
    }
}
