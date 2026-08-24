<?php

namespace App\Console\Commands\AccountingEvent;

use App\Enums\PRFEntryType;
use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Enums\PRFMorphType;
use App\Models\MissionType;
use App\Models\School;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\Models\Tenant;

class FillBudgetSummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fill-budget-summaries {--dry-run : Run without saving to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed per-mission-type budget estimates and mission defaults for schools from historical spend';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Running in dry-run mode - no data will be saved');
        }

        $this->info('📊 Starting budget estimate generation...');

        $tenants = Tenant::query()->get();
        $totals = ['createdEstimates' => 0, 'createdEntries' => 0, 'updatedDefaults' => 0, 'skipped' => 0, 'errors' => 0];

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            $this->processTenant($dryRun, $totals);

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
                ['Schools skipped', $totals['skipped']],
                ['Errors', $totals['errors']],
            ]
        );

        if ($dryRun) {
            $this->info('💡 This was a dry-run. Run without --dry-run to save changes.');
        }

        return $totals['errors'] > 0 ? 1 : 0;
    }

    /**
     * @param  array{createdEstimates: int, createdEntries: int, updatedDefaults: int, skipped: int, errors: int}  $totals
     */
    private function processTenant(bool $dryRun, array &$totals): void
    {
        $servicedStatuses = [PRFMissionStatus::SERVICED, PRFMissionStatus::FULLY_SUBSCRIBED];

        // Schools with serviced missions
        $schools = School::query()
            ->whereHas('missions', fn ($query) => $query->whereIn('status', $servicedStatuses))
            ->get();

        if ($schools->isEmpty()) {
            return;
        }

        $this->info('Tenant '.tenancy()->tenant->id.": processing {$schools->count()} schools with serviced missions");

        $progressBar = $this->output->createProgressBar($schools->count());
        $progressBar->start();

        foreach ($schools as $school) {
            try {
                // Historical spend grouped by (mission type, expense category)
                // across ALL serviced missions of that type at this school
                $rows = DB::table('allocation_entries as ae')
                    ->join('accounting_events as ev', 'ev.id', '=', 'ae.accounting_event_id')
                    ->join('missions as m', function ($join) {
                        $join->on('m.id', '=', 'ev.accounting_eventable_id')
                            ->where('ev.accounting_eventable_type', '=', PRFMorphType::MISSION);
                    })
                    ->where('m.school_id', $school->id)
                    ->whereIn('m.status', $servicedStatuses)
                    ->whereNull('ae.deleted_at')
                    ->whereNull('m.deleted_at')
                    ->where('ae.entry_type', PRFEntryType::DEBIT)
                    ->whereNotNull('ae.expense_category_id')
                    ->groupBy('m.mission_type_id', 'ae.expense_category_id')
                    ->selectRaw('m.mission_type_id')
                    ->selectRaw('ae.expense_category_id')
                    ->selectRaw('round(avg(ae.unit_cost)) as avg_unit_cost')
                    ->selectRaw('greatest(1, round(avg(ae.quantity))) as avg_quantity')
                    ->selectRaw('count(*) as entry_count')
                    ->get();

                if ($rows->isEmpty()) {
                    $totals['skipped']++;
                    $progressBar->advance();

                    continue;
                }

                // Mission types present at this school
                $missionTypeIds = $rows->pluck('mission_type_id')->unique()->values();

                if (! $dryRun) {
                    DB::transaction(function () use ($school, $rows, $missionTypeIds, $servicedStatuses, &$totals) {
                        foreach ($missionTypeIds as $missionTypeId) {
                            $typeRows = $rows->where('mission_type_id', $missionTypeId);
                            $missionCount = $this->missionCount((int) $school->id, (int) $missionTypeId, $servicedStatuses);

                            // One ACTIVE estimate per (school, mission type)
                            $estimate = $school->budgetEstimates()->create([
                                'mission_type_id' => $missionTypeId,
                                'baseline_people' => $this->avgPeople((int) $school->id, (int) $missionTypeId, $servicedStatuses),
                            ]);
                            $totals['createdEstimates']++;

                            foreach ($typeRows as $row) {
                                $unitPrice = (int) round($row->avg_unit_cost);
                                $quantity = max(1, (int) round($row->avg_quantity));

                                $estimate->budgetEstimateEntries()->create([
                                    'expense_category_id' => $row->expense_category_id,
                                    'item_name' => $this->categoryName((int) $row->expense_category_id),
                                    'unit_price' => $unitPrice,
                                    'quantity' => $quantity,
                                    'total_price' => $unitPrice * $quantity,
                                    'notes' => sprintf(
                                        'Seeded from %d historical mission(s), %d expense entr(ies)',
                                        $missionCount,
                                        $row->entry_count,
                                    ),
                                ]);
                                $totals['createdEntries']++;
                            }
                        }

                        // Per-type mission defaults from historical data
                        $entries = [];
                        foreach ($missionTypeIds as $missionTypeId) {
                            $defaults = $this->typeDefaults((int) $school->id, (int) $missionTypeId, $servicedStatuses);

                            if (filled($defaults)) {
                                $entries[] = ['mission_type_id' => (int) $missionTypeId, ...$defaults];
                            }
                        }

                        if (filled($entries)) {
                            $school->setMissionTypeDefaults(
                                $entries,
                                defaultMissionTypeId: $this->defaultMissionTypeId($missionTypeIds),
                            );
                            $totals['updatedDefaults'] += count($entries);
                        }
                    });
                } else {
                    $totals['createdEstimates'] += $missionTypeIds->count();
                    $totals['createdEntries'] += $rows->count();
                }
            } catch (Exception $e) {
                $totals['errors']++;
                $this->error("\nError processing school {$school->name}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
    }

    /**
     * Average non-withdrawn subscription count per mission of this type at the
     * school; falls back to average capacity when no subscriptions exist.
     *
     * @param  array<int, int>  $servicedStatuses
     */
    private function avgPeople(int $schoolId, int $missionTypeId, array $servicedStatuses): int
    {
        $perMissionPeople = DB::table('missions as m')
            ->leftJoin('mission_subscriptions as ms', function ($join) {
                $join->on('ms.mission_id', '=', 'm.id')
                    ->where('ms.status', '!=', PRFMissionSubscriptionStatus::WITHDRAWN)
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
