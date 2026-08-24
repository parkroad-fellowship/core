<?php

namespace App\Console\Commands\AccountingEvent;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFEntryType;
use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Enums\PRFMorphType;
use App\Helpers\Utils;
use App\Models\ExpenseCategory;
use App\Models\MissionType;
use App\Models\School;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\Models\Tenant;

class FillBudgetSummaries extends Command
{
    /**
     * Standard cost per person for snacks, regardless of historical variance.
     */
    private const SNACKS_RATE_PER_PERSON = 250;

    /**
     * Per-person unit costs below this floor are treated as data-entry noise.
     */
    private const PER_PERSON_RATE_FLOOR = 50;

    /**
     * Per-person unit costs above this ceiling are treated as lump-sum
     * vehicle/group hires misrecorded at quantity=1, not individual fares.
     */
    private const PER_PERSON_RATE_CEILING = 5000;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fill-budget-summaries
        {--dry-run : Run without saving to database}
        {--refresh : Replace existing budget estimates for the processed schools}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed per-mission-type budget estimates and mission defaults for schools from historical spend';

    /**
     * Cached per-tenant lookup state.
     */
    private ?int $snacksCategoryId = null;

    private ?int $fareCategoryId = null;

    private ?int $chargesCategoryId = null;

    /** @var array<int, int> */
    private array $perPersonCategoryIds = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $refresh = (bool) $this->option('refresh');

        if ($dryRun) {
            $this->info('🔍 Running in dry-run mode - no data will be saved');
        }

        $this->info('📊 Starting budget estimate generation...'.($refresh ? ' (refresh mode)' : ''));

        $tenants = Tenant::query()->get();
        $totals = [
            'createdEstimates' => 0,
            'createdEntries' => 0,
            'updatedDefaults' => 0,
            'skippedSchools' => 0,
            'skippedPairs' => 0,
            'errors' => 0,
        ];

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            $this->processTenant($dryRun, $refresh, $totals);

            tenancy()->end();
        }

        // Summary
        $this->newLine(2);
        $this->info('✅ Process completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Budget estimates created', $totals['createdEstimates']],
                ['Estimate entries created', $totals['createdEntries']],
                ['Mission type defaults saved', $totals['updatedDefaults']],
                ['Schools skipped (no history)', $totals['skippedSchools']],
                ['School+type pairs skipped (existing)', $totals['skippedPairs']],
                ['Errors', $totals['errors']],
            ]
        );

        if ($dryRun) {
            $this->info('💡 This was a dry-run. Run without --dry-run to save changes.');
        }

        return $totals['errors'] > 0 ? 1 : 0;
    }

    /**
     * @param  array{createdEstimates: int, createdEntries: int, updatedDefaults: int, skippedSchools: int, skippedPairs: int, errors: int}  $totals
     */
    private function processTenant(bool $dryRun, bool $refresh, array &$totals): void
    {
        $servicedStatuses = [PRFMissionStatus::SERVICED->value, PRFMissionStatus::FULLY_SUBSCRIBED->value];

        $schools = School::query()
            ->whereHas('missions', fn ($query) => $query->whereIn('status', $servicedStatuses))
            ->get();

        if ($schools->isEmpty()) {
            return;
        }

        $this->info('Tenant '.tenancy()->tenant->id.": processing {$schools->count()} schools with serviced missions");

        // Per-tenant reference data (creates the transfer-charges category on demand)
        ExpenseCategory::updateOrCreate(
            ['name' => 'Transaction Charges'],
            [
                'description' => 'M-Pesa / bank transfer costs incurred while disbursing mission funds',
                'is_active' => PRFActiveStatus::ACTIVE,
            ],
        );

        $this->chargesCategoryId = (int) ExpenseCategory::query()->where('name', 'Transaction Charges')->value('id');
        $this->snacksCategoryId = (int) (ExpenseCategory::query()->where('name', 'Snacks')->value('id') ?? 0);
        $this->fareCategoryId = (int) (ExpenseCategory::query()->where('name', 'Fare')->value('id') ?? 0);
        $this->perPersonCategoryIds = ExpenseCategory::query()
            ->where('is_per_person', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $progressBar = $this->output->createProgressBar($schools->count());
        $progressBar->start();

        foreach ($schools as $school) {
            try {
                $this->processSchool($school, $servicedStatuses, $dryRun, $refresh, $totals);
            } catch (Exception $e) {
                $totals['errors']++;
                $this->error("\nError processing school {$school->name}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * @param  array<int, int>  $servicedStatuses
     * @param  array{createdEstimates: int, createdEntries: int, updatedDefaults: int, skippedSchools: int, skippedPairs: int, errors: int}  $totals
     */
    private function processSchool(School $school, array $servicedStatuses, bool $dryRun, bool $refresh, array &$totals): void
    {
        // Historical debit entries across all serviced missions of this school
        $entries = DB::table('allocation_entries as ae')
            ->join('accounting_events as ev', 'ev.id', '=', 'ae.accounting_event_id')
            ->join('missions as m', function ($join) {
                $join->on('m.id', '=', 'ev.accounting_eventable_id')
                    ->where('ev.accounting_eventable_type', '=', PRFMorphType::MISSION);
            })
            ->where('m.school_id', $school->id)
            ->whereIn('m.status', $servicedStatuses)
            ->whereNull('ae.deleted_at')
            ->whereNull('m.deleted_at')
            ->where('ae.entry_type', PRFEntryType::DEBIT->value)
            ->whereNotNull('ae.expense_category_id')
            ->selectRaw('m.mission_type_id as mission_type_id')
            ->selectRaw('ae.expense_category_id as expense_category_id')
            ->selectRaw('ae.unit_cost as unit_cost')
            ->selectRaw('ae.quantity as quantity')
            ->selectRaw('ae.amount as amount')
            ->selectRaw('ae.charge as charge')
            ->selectRaw('m.id as mission_id')
            ->get();

        if ($entries->isEmpty()) {
            $totals['skippedSchools']++;

            return;
        }

        $missionTypeIds = $entries->pluck('mission_type_id')->unique()->values();

        if ($dryRun) {
            foreach ($missionTypeIds as $missionTypeId) {
                if (! $refresh && $this->hasExistingEstimate($school, (int) $missionTypeId)) {
                    $totals['skippedPairs']++;

                    continue;
                }
                $totals['createdEstimates']++;
                $totals['createdEntries'] += $this->countPlannedEntries(
                    $entries->where('mission_type_id', $missionTypeId),
                    (int) $school->id,
                    (int) $missionTypeId,
                    $servicedStatuses,
                );
            }

            return;
        }

        DB::transaction(function () use ($school, $entries, $missionTypeIds, $servicedStatuses, $refresh, &$totals) {
            foreach ($missionTypeIds as $missionTypeId) {
                $typeId = (int) $missionTypeId;

                if (! $refresh && $this->hasExistingEstimate($school, $typeId)) {
                    $totals['skippedPairs']++;

                    continue;
                }

                if ($refresh) {
                    $this->deleteExistingEstimates($school, $typeId);
                }

                $baselinePeople = $this->avgPeople((int) $school->id, $typeId, $servicedStatuses);
                $missionPeople = $this->missionPeopleMap((int) $school->id, $typeId, $servicedStatuses, $baselinePeople);
                $missionCount = max(1, $this->missionCount((int) $school->id, $typeId, $servicedStatuses));

                // One ACTIVE estimate per (school, mission type)
                $estimate = $school->budgetEstimates()->create([
                    'mission_type_id' => $typeId,
                    'baseline_people' => $baselinePeople,
                ]);
                $totals['createdEstimates']++;

                $typeEntries = $entries->where('mission_type_id', $missionTypeId);
                $categoryIds = $typeEntries->pluck('expense_category_id')->unique()->values();

                // Always carry a standard snacks line
                if ($this->snacksCategoryId > 0 && ! $categoryIds->contains($this->snacksCategoryId)) {
                    $categoryIds->push($this->snacksCategoryId);
                }

                foreach ($categoryIds as $categoryId) {
                    // Fare: copy the latest mission's legs verbatim so
                    // multi-stop / to-and-fro entries stay distinct lines.
                    if ((int) $categoryId === $this->fareCategoryId) {
                        foreach ($this->latestMissionFareEntries((int) $school->id, $typeId, $servicedStatuses) as $leg) {
                            $total = (int) $leg->unit_cost * (int) $leg->quantity;

                            $estimate->budgetEstimateEntries()->create([
                                'expense_category_id' => $categoryId,
                                'item_name' => 'Fare',
                                'unit_price' => (int) $leg->unit_cost,
                                'quantity' => (int) $leg->quantity,
                                'total_price' => $total,
                                'cost' => $this->estimateCost($total),
                                'notes' => trim(sprintf(
                                    'Seeded from %s mission. %s',
                                    \Carbon\Carbon::parse($leg->start_date ?? now())->format('d M Y'),
                                    $leg->narration ?? '',
                                )),
                            ]);
                            $totals['createdEntries']++;
                        }

                        continue;
                    }

                    $derived = $this->deriveEntry(
                        (int) $categoryId,
                        $typeEntries->where('expense_category_id', $categoryId),
                        $baselinePeople,
                        $missionPeople,
                        (int) $school->id,
                        $typeId,
                        $servicedStatuses,
                    );

                    if ($derived === null) {
                        continue;
                    }

                    [$unitPrice, $quantity] = $derived;
                    $totalPrice = $unitPrice * $quantity;

                    $estimate->budgetEstimateEntries()->create([
                        'expense_category_id' => $categoryId,
                        'item_name' => $this->categoryName((int) $categoryId),
                        'unit_price' => $unitPrice,
                        'quantity' => $quantity,
                        'total_price' => $totalPrice,
                        'cost' => $this->estimateCost($totalPrice),
                        'notes' => sprintf(
                            'Seeded from %d historical mission(s)',
                            $missionCount,
                        ),
                    ]);
                    $totals['createdEntries']++;
                }
            }

            // Per-type mission defaults from historical data
            $defaults = [];
            foreach ($missionTypeIds as $missionTypeId) {
                $typeDefaults = $this->typeDefaults((int) $school->id, (int) $missionTypeId, $servicedStatuses);

                if (filled($typeDefaults)) {
                    $defaults[] = ['mission_type_id' => (int) $missionTypeId, ...$typeDefaults];
                }
            }

            if (filled($defaults)) {
                $school->setMissionTypeDefaults(
                    $defaults,
                    defaultMissionTypeId: $this->defaultMissionTypeId($missionTypeIds),
                );
                $totals['updatedDefaults'] += count($defaults);
            }
        });
    }

    /**
     * Derive a single estimate entry.
     *
     * Fare is handled separately (verbatim legs of the latest mission).
     * Other per-person categories: median of PER-MISSION per-person spend
     * (all legs summed, divided by that mission's headcount; floor/ceiling
     * filtered) x headcount. Snacks: standard rate per person x headcount.
     * Fixed-cost categories: average net spend per mission x 1.
     *
     * @param  Collection<int, object>  $entries
     * @param  array<int, int>  $servicedStatuses
     * @return array{0: int, 1: int}|null
     */
    private function deriveEntry(
        int $categoryId,
        Collection $entries,
        int $baselinePeople,
        Collection $missionPeople,
        int $schoolId,
        int $missionTypeId,
        array $servicedStatuses,
    ): ?array {
        if ($categoryId === $this->snacksCategoryId) {
            return [self::SNACKS_RATE_PER_PERSON, $baselinePeople];
        }

        if (in_array($categoryId, $this->perPersonCategoryIds, true)) {
            // Fare is often recorded across multiple stops / both directions.
            // Derive what ONE person actually spent travelling, PER MISSION:
            // sum every leg of the mission, then divide by that mission's headcount.
            $rates = $entries
                ->groupBy('mission_id')
                ->map(function ($legs) use ($baselinePeople, $missionPeople) {
                    $netSpend = $legs->sum(fn ($entry) => $entry->amount - $entry->charge);
                    $people = $missionPeople->get((int) $legs->first()->mission_id, $baselinePeople);

                    return $netSpend / max(1, $people);
                })
                ->filter(fn ($rate) => $rate >= self::PER_PERSON_RATE_FLOOR)
                ->filter(fn ($rate) => $rate <= self::PER_PERSON_RATE_CEILING)
                ->values();

            if ($rates->isEmpty()) {
                return null;
            }

            return [(int) round($this->median($rates)), $baselinePeople];
        }

        // Fixed cost: pure net spend (transfer fees excluded) spread over missions
        $netSpend = $entries->sum(fn ($entry) => $entry->amount - $entry->charge);

        if ($netSpend <= 0) {
            return null;
        }

        $missionCount = max(1, $this->missionCount($schoolId, $missionTypeId, $servicedStatuses));

        return [(int) round($netSpend / $missionCount), 1];
    }

    /**
     * @param  Collection<int, object>  $typeEntries
     */
    private function countPlannedEntries(Collection $typeEntries, int $schoolId, int $missionTypeId, array $servicedStatuses): int
    {
        $baselinePeople = $this->avgPeople($schoolId, $missionTypeId, $servicedStatuses);
        $missionPeople = $this->missionPeopleMap($schoolId, $missionTypeId, $servicedStatuses, $baselinePeople);
        $categoryIds = $typeEntries->pluck('expense_category_id')->unique()->values();

        if ($this->snacksCategoryId > 0 && ! $categoryIds->contains($this->snacksCategoryId)) {
            $categoryIds->push($this->snacksCategoryId);
        }

        $count = 0;
        foreach ($categoryIds as $categoryId) {
            if ((int) $categoryId === $this->fareCategoryId) {
                $count += $this->latestMissionFareEntries($schoolId, $missionTypeId, $servicedStatuses)->count();

                continue;
            }

            if ($this->deriveEntry((int) $categoryId, $typeEntries->where('expense_category_id', $categoryId), $baselinePeople, $missionPeople, $schoolId, $missionTypeId, $servicedStatuses) !== null) {
                $count++;
            }
        }

        return $count;
    }

    private function hasExistingEstimate(School $school, int $missionTypeId): bool
    {
        return $school->budgetEstimates()
            ->withTrashed()
            ->where('mission_type_id', $missionTypeId)
            ->exists();
    }

    private function deleteExistingEstimates(School $school, int $missionTypeId): void
    {
        $school->budgetEstimates()
            ->withTrashed()
            ->where('mission_type_id', $missionTypeId)
            ->get()
            ->each(fn ($estimate) => $estimate->forceDelete());
    }

    /**
     * @param  array<int, float|int>  $values
     */
    private function median(Collection $values): float
    {
        $sorted = $values->sort()->values();
        $count = $sorted->count();
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float) $sorted->get($middle);
        }

        return ((float) $sorted->get($middle - 1) + (float) $sorted->get($middle)) / 2;
    }

    private function estimateCost(int $amount): int
    {
        return Utils::estimateTransferCharge($amount);
    }

    /**
     * Fare entries of the most recent serviced mission of this school + type,
     * copied verbatim so multi-stop / to-and-fro legs stay distinct lines.
     *
     * @param  array<int, int>  $servicedStatuses
     * @return Collection<int, object>
     */
    private function latestMissionFareEntries(int $schoolId, int $missionTypeId, array $servicedStatuses): Collection
    {
        if ($this->fareCategoryId === 0) {
            return collect();
        }

        $latestMissionId = DB::table('missions as m')
            ->join('accounting_events as ev', function ($join) {
                $join->on('ev.accounting_eventable_id', '=', 'm.id')
                    ->where('ev.accounting_eventable_type', '=', PRFMorphType::MISSION);
            })
            ->join('allocation_entries as ae', function ($join) {
                $join->on('ae.accounting_event_id', '=', 'ev.id')
                    ->where('ae.entry_type', '=', PRFEntryType::DEBIT->value)
                    ->where('ae.expense_category_id', '=', $this->fareCategoryId)
                    ->whereNull('ae.deleted_at');
            })
            ->where('m.school_id', $schoolId)
            ->where('m.mission_type_id', $missionTypeId)
            ->whereIn('m.status', $servicedStatuses)
            ->whereNull('m.deleted_at')
            ->orderByDesc('m.start_date')
            ->value('m.id');

        if (! $latestMissionId) {
            return collect();
        }

        return DB::table('allocation_entries as ae')
            ->join('accounting_events as ev', 'ev.id', '=', 'ae.accounting_event_id')
            ->join('missions as m', 'm.id', '=', 'ev.accounting_eventable_id')
            ->where('ev.accounting_eventable_id', $latestMissionId)
            ->where('ev.accounting_eventable_type', PRFMorphType::MISSION->value)
            ->where('ae.expense_category_id', $this->fareCategoryId)
            ->where('ae.entry_type', PRFEntryType::DEBIT->value)
            ->whereNull('ae.deleted_at')
            ->selectRaw('ae.unit_cost as unit_cost')
            ->selectRaw('ae.quantity as quantity')
            ->selectRaw('ae.narration as narration')
            ->selectRaw('m.start_date as start_date')
            ->get();
    }

    /**
     * Average non-withdrawn subscription count per mission of this type at the
     * school; falls back to average capacity when no subscriptions exist.
     *
     * @param  array<int, int>  $servicedStatuses
     */
    /**
     * Headcount (non-withdrawn subscriptions, falling back to capacity then
     * baseline) for each historical mission of this school + type.
     *
     * @param  array<int, int>  $servicedStatuses
     * @return Collection<int, int>
     */
    private function missionPeopleMap(int $schoolId, int $missionTypeId, array $servicedStatuses, int $fallback): Collection
    {
        $subCounts = DB::table('missions as m')
            ->leftJoin('mission_subscriptions as ms', function ($join) {
                $join->on('ms.mission_id', '=', 'm.id')
                    ->where('ms.status', '!=', PRFMissionSubscriptionStatus::WITHDRAWN->value)
                    ->whereNull('ms.deleted_at');
            })
            ->where('m.school_id', $schoolId)
            ->where('m.mission_type_id', $missionTypeId)
            ->whereIn('m.status', $servicedStatuses)
            ->whereNull('m.deleted_at')
            ->groupBy('m.id')
            ->selectRaw('m.id as mission_id')
            ->selectRaw('count(ms.id) as people')
            ->selectRaw('max(m.capacity) as capacity')
            ->get();

        return $subCounts->mapWithKeys(fn ($row) => [
            (int) $row->mission_id => max(1, (int) ($row->people > 0 ? $row->people : ($row->capacity ?: $fallback))),
        ]);
    }

    private function avgPeople(int $schoolId, int $missionTypeId, array $servicedStatuses): int
    {
        $perMissionPeople = DB::table('missions as m')
            ->leftJoin('mission_subscriptions as ms', function ($join) {
                $join->on('ms.mission_id', '=', 'm.id')
                    ->where('ms.status', '!=', PRFMissionSubscriptionStatus::WITHDRAWN->value)
                    ->whereNull('ms.deleted_at');
            })
            ->where('m.school_id', $schoolId)
            ->where('m.mission_type_id', $missionTypeId)
            ->whereIn('m.status', $servicedStatuses)
            ->whereNull('m.deleted_at')
            ->groupBy('m.id')
            ->selectRaw('count(ms.id) as people')
            ->pluck('people');

        if ($perMissionPeople->sum() > 0) {
            return max(1, (int) round($perMissionPeople->avg()));
        }

        $avgCapacity = DB::table('missions')
            ->where('school_id', $schoolId)
            ->where('mission_type_id', $missionTypeId)
            ->whereIn('status', $servicedStatuses)
            ->whereNull('deleted_at')
            ->avg('capacity');

        return max(1, (int) round($avgCapacity ?: 1));
    }

    /**
     * @param  array<int, int>  $servicedStatuses
     */
    private function missionCount(int $schoolId, int $missionTypeId, array $servicedStatuses): int
    {
        return DB::table('missions')
            ->where('school_id', $schoolId)
            ->where('mission_type_id', $missionTypeId)
            ->whereIn('status', $servicedStatuses)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Most common start/end times and average capacity for this school + type.
     *
     * @param  array<int, int>  $servicedStatuses
     * @return array{start_time?: string, end_time?: string, capacity?: int}
     */
    private function typeDefaults(int $schoolId, int $missionTypeId, array $servicedStatuses): array
    {
        $row = DB::table('missions')
            ->where('school_id', $schoolId)
            ->where('mission_type_id', $missionTypeId)
            ->whereIn('status', $servicedStatuses)
            ->whereNull('deleted_at')
            ->whereNotNull('start_time')
            ->selectRaw("mode() within group (order by to_char(start_time, 'HH24:MI')) as common_start")
            ->selectRaw("mode() within group (order by to_char(end_time, 'HH24:MI')) as common_end")
            ->selectRaw('round(avg(capacity)) as avg_capacity')
            ->first();

        return array_filter([
            'start_time' => $row?->common_start,
            'end_time' => $row?->common_end,
            'capacity' => $row?->avg_capacity !== null ? (int) $row->avg_capacity : null,
        ], fn ($value) => filled($value));
    }

    private function categoryName(int $categoryId): string
    {
        return DB::table('expense_categories')->where('id', $categoryId)->value('name') ?? 'Miscellaneous Expense';
    }

    /**
     * Prefer the fallback mission type (Sunday Service) as the school's default.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $missionTypeIds
     */
    private function defaultMissionTypeId(\Illuminate\Support\Collection $missionTypeIds): ?int
    {
        $fallbackId = MissionType::defaultFallback()?->id;

        if ($fallbackId && $missionTypeIds->contains($fallbackId)) {
            return $fallbackId;
        }

        return $missionTypeIds->first();
    }
}
