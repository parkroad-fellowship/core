<?php

namespace App\Services;

use App\Enums\PRFEntryType;
use App\Enums\PRFMissionStatus;
use App\Models\Mission;

class MissionCompletionService
{
    /**
     * What a mission needs before it can be marked as serviced. Required items block completion;
     * optional ones are shown as reminders.
     *
     * @return list<array{key: string, label: string, done: bool, required: bool}>
     */
    public function checklist(Mission $mission): array
    {
        $items = [];

        foreach ($this->checks($mission) as $key => $check) {
            $items[] = [
                'key' => $key,
                'label' => $check['description'],
                'done' => $check['passed'],
                'required' => $check['required'],
            ];
        }

        return $items;
    }

    /**
     * The labels of required checklist items that are not done yet.
     *
     * @return list<string>
     */
    public function missingRequiredItems(Mission $mission): array
    {
        return array_values(array_map(
            fn(array $check): string => $check['label'],
            array_filter($this->checks($mission), fn(array $check): bool => $check['required'] && !$check['passed']),
        ));
    }

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
        $checks = $this->checks($mission);

        $failedChecks = array_values(array_map(
            fn(array $check): string => $check['label'],
            array_filter($checks, fn(array $check): bool => $check['required'] && !$check['passed']),
        ));

        return [
            'can_complete' => $failedChecks === [],
            'checks' => $checks,
            'message' => $failedChecks === []
                ? 'All requirements met. Mission can be marked as completed.'
                : 'Please complete: ' . implode(', ', $failedChecks),
        ];
    }

    /**
     * Marks the mission as serviced when every required item is done. Call it through
     * App\Jobs\Mission\CompleteJob, which checks the status transition first.
     */
    public function completeMission(Mission $mission): bool
    {
        if ($this->missingRequiredItems($mission) !== []) {
            return false;
        }

        $mission->status->moveTo(PRFMissionStatus::SERVICED);

        return true;
    }

    /**
     * Check if a mission can bypass the checklist (e.g., already serviced).
     */
    public function canBypassChecklist(Mission $mission): bool
    {
        return $mission->status->is(PRFMissionStatus::SERVICED);
    }

    /**
     * @return array<string, array{passed: bool, required: bool, label: string, description: string, count: int|null}>
     */
    private function checks(Mission $mission): array
    {
        $checks = [];

        $photoCount = $mission->getMedia(Mission::MISSION_PHOTOS)->count();
        $checks['photos'] = [
            'passed' => $photoCount >= 1,
            'required' => false,
            'label' => 'Mission Photos',
            'description' => $photoCount >= 1
                ? "{$photoCount} photo(s) uploaded"
                : 'Optional: upload at least 1 mission photo',
            'count' => $photoCount,
        ];

        $noteCount = $mission->debriefNotes()->count();
        $checks['debrief_notes'] = [
            'passed' => $noteCount >= 1,
            'required' => true,
            'label' => 'Debrief Notes',
            'description' => $noteCount >= 1
                ? "{$noteCount} debrief note(s) recorded"
                : 'Required: record at least 1 debrief note',
            'count' => $noteCount,
        ];

        $soulCount = $mission->souls()->count();
        $checks['souls'] = [
            'passed' => $soulCount >= 1,
            'required' => false,
            'label' => 'Souls / Students',
            'description' => $soulCount >= 1
                ? "{$soulCount} soul(s) recorded"
                : 'Optional: record the souls / students reached',
            'count' => $soulCount,
        ];

        $accountingEvent = $mission->accountingEvent;

        if ($accountingEvent) {
            $credits = (int) $accountingEvent
                ->allocationEntries()
                ->where('entry_type', PRFEntryType::CREDIT)
                ->sum('amount');

            if ($credits > 0) {
                $debits = (int) $accountingEvent
                    ->allocationEntries()
                    ->where('entry_type', PRFEntryType::DEBIT)
                    ->sum('amount');

                $hasExpenseEntries = $debits > 0;

                $checks['finances'] = [
                    'passed' => $hasExpenseEntries,
                    'required' => true,
                    'label' => 'Financial Records',
                    'description' => $hasExpenseEntries
                        ? 'Expenses recorded (KES '
                        . number_format($debits)
                        . ' of '
                        . number_format($credits)
                        . ' spent)'
                        : 'Required: record the expenses for the KES ' . number_format($credits) . ' issued',
                    'count' => $debits,
                ];
            }
        }

        return $checks;
    }
}
