<?php

namespace App\Console\Commands\AccountingEvent;

use App\Enums\PRFEntryType;
use App\Enums\PRFMorphType;
use App\Models\BudgetEstimate;
use App\Models\BudgetEstimateEntry;
use App\Models\School;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
    protected $description = 'Create default budget estimates for schools based on their latest mission allocation entries';

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

        // Get all schools that have missions
        $schools = School::query()
            ->whereHas('missions')
            ->with([
                'missions' => fn ($query) => $query->latest('start_date')
                    ->with([
                        'accountingEvent.allocationEntries' => fn ($q) => $q
                            ->where('entry_type', PRFEntryType::DEBIT->value)
                            ->with('expenseCategory'),
                    ]),
            ])
            ->get();

        $this->info("Found {$schools->count()} schools with missions");

        $progressBar = $this->output->createProgressBar($schools->count());
        $progressBar->start();

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($schools as $school) {
            try {
                // Get the latest mission
                $latestMission = $school->missions->first();

                if (! $latestMission) {
                    $skipped++;
                    $progressBar->advance();

                    continue;
                }

                // Check if accounting event exists with allocation entries
                if (! $latestMission->accountingEvent || $latestMission->accountingEvent->allocationEntries->isEmpty()) {
                    $skipped++;
                    $progressBar->advance();

                    continue;
                }

                // Group allocation entries by expense category
                $groupedEntries = $latestMission->accountingEvent->allocationEntries
                    ->groupBy('expense_category_id');

                if ($groupedEntries->isEmpty()) {
                    $skipped++;
                    $progressBar->advance();

                    continue;
                }

                if (! $dryRun) {
                    DB::transaction(function () use ($school, $groupedEntries) {
                        // Calculate grand total
                        $grandTotal = $groupedEntries->sum(function ($entries) {
                            return $entries->sum('amount');
                        });

                        // Create budget estimate
                        $budgetEstimate = BudgetEstimate::create([
                            'budget_estimatable_id' => $school->id,
                            'budget_estimatable_type' => PRFMorphType::SCHOOL,
                            'grand_total' => $grandTotal,

                        ]);

                        // Create budget estimate entries
                        foreach ($groupedEntries as $categoryId => $entries) {
                            $totalAmount = $entries->sum('amount');
                            $totalQuantity = $entries->sum('quantity') ?: 1;
                            $avgUnitPrice = $totalQuantity > 0 ? round($totalAmount / $totalQuantity) : $totalAmount;

                            // Get category name for item name
                            $expenseCategory = $entries->first()->expenseCategory;
                            $itemName = $expenseCategory?->name ?? 'Miscellaneous Expense';

                            BudgetEstimateEntry::create([
                                'budget_estimate_id' => $budgetEstimate->id,
                                'expense_category_id' => $categoryId,
                                'item_name' => $itemName,
                                'unit_price' => $avgUnitPrice,
                                'quantity' => $totalQuantity,
                                'total_price' => $totalAmount,
                                'notes' => 'Auto-generated from latest mission data',
                            ]);
                        }
                    });
                }

                $created++;
            } catch (Exception $e) {
                $errors++;
                $this->error("\nError processing school {$school->name}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('✅ Process completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Budget estimates created', $created],
                ['Schools skipped', $skipped],
                ['Errors', $errors],
            ]
        );

        if ($dryRun) {
            $this->info('💡 This was a dry-run. Run without --dry-run to save changes.');
        }

        return $errors > 0 ? 1 : 0;
    }
}
