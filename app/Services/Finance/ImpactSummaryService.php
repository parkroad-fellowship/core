<?php

namespace App\Services\Finance;

use App\Enums\PRFCompletionStatus;
use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFLedgerFlow;
use App\Enums\PRFMissionStatus;
use App\Enums\PRFSoulDecisionType;
use App\Models\CourseMember;
use App\Models\LedgerEntry;
use App\Models\Mission;
use App\Models\Soul;
use Illuminate\Support\Carbon;

/**
 * What the fellowship's giving achieved in a period: missions, students, souls and discipleship.
 */
class ImpactSummaryService
{
    public function for(Carbon $from, Carbon $to): ImpactSummary
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $missions = Mission::query()
            ->where('status', PRFMissionStatus::SERVICED)
            ->whereBetween('start_date', [$from, $to]);

        $decisions = Soul::query()
            ->whereIn('mission_id', (clone $missions)->select('id'))
            ->selectRaw('decision_type, count(*) as souls')
            ->groupBy('decision_type')
            ->pluck('souls', 'decision_type');

        return new ImpactSummary(
            from: $from,
            to: $to,
            missionsServiced: (clone $missions)->count(),
            studentsReached: (int) (clone $missions)->join('schools', 'schools.id', '=', 'missions.school_id')->sum(
                'schools.total_students',
            ),
            souls: (int) $decisions->sum(),
            decisions: $decisions->mapWithKeys(fn($souls, $type) => [
                PRFSoulDecisionType::from((int) $type)->getLabel() => (int) $souls,
            ])->all(),
            inDiscipleship: CourseMember::query()->where('completion_status', PRFCompletionStatus::INCOMPLETE)->count(),
            incomeReceived: (int) LedgerEntry::query()
                ->where('flow', PRFLedgerFlow::RECEIPT)
                ->ofKind(PRFLedgerCategoryKind::INCOME)
                ->between($from, $to)
                ->sum('amount'),
        );
    }

    /**
     * Year to date, which is what receipts share with givers.
     */
    public function yearToDate(?Carbon $asOf = null): ImpactSummary
    {
        $asOf ??= Carbon::today();

        return $this->for($asOf->copy()->startOfYear(), $asOf);
    }
}
