<?php

namespace App\Jobs\AccountingEvent;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Helpers\Utils;
use App\Models\AccountingEvent;
use App\Models\BudgetEstimate;
use App\Models\BudgetEstimateEntry;
use App\Models\Member;
use App\Models\Mission;
use App\Models\Requisition;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateDefaultRequisitionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AccountingEvent $accountingEvent,
    ) {
        //
    }

    public function handle(): void
    {
        $mission = Mission::query()
            ->with(['school', 'missionType'])
            ->find($this->accountingEvent->accounting_eventable_id);

        if (! $mission?->school || ! $mission?->mission_type_id) {
            return;
        }

        $budgetEstimate = $mission->school->getBudgetEstimateFor($mission->mission_type_id);

        if (! $budgetEstimate) {
            Log::info('No active budget estimate found for mission requisition auto-creation.', [
                'accounting_event_ulid' => $this->accountingEvent->ulid,
                'mission_ulid' => $mission->ulid,
                'school_ulid' => $mission->school->ulid,
                'mission_type_id' => $mission->mission_type_id,
            ]);

            return;
        }

        try {
            $member = Member::query() // Get desk head email
                ->whereEmail(Utils::getDeskEmails($this->accountingEvent->responsible_desk)[0])
                ->firstOrFail();
        } catch (\Throwable) {
            Log::warning('Could not resolve desk member for requisition auto-creation.', [
                'accounting_event_ulid' => $this->accountingEvent->ulid,
                'responsible_desk' => $this->accountingEvent->responsible_desk,
            ]);

            return;
        }

        DB::transaction(function () use ($member, $budgetEstimate, $mission) {
            /** @var array<int, array<string, mixed>> $items */
            $items = [];
            $totalAmount = 0;

            BudgetEstimateEntry::query()
                ->where('budget_estimate_id', $budgetEstimate->id)
                ->with('expenseCategory')
                ->chunk(50, function ($budgetEstimateEntries) use (&$items, &$totalAmount, $budgetEstimate, $mission) {
                    foreach ($budgetEstimateEntries as $budgetEstimateEntry) {
                        $quantity = $this->scaledQuantity($budgetEstimateEntry, $budgetEstimate, $mission);
                        $unitPrice = (int) $budgetEstimateEntry->unit_price;
                        $totalPrice = $unitPrice * $quantity;

                        $items[] = [
                            'expense_category_id' => $budgetEstimateEntry->expense_category_id,
                            'item_name' => $budgetEstimateEntry->item_name,
                            'narration' => $budgetEstimateEntry->notes ?? 'N/A',
                            'unit_price' => $unitPrice,
                            'quantity' => $quantity,
                            'total_price' => $totalPrice,
                        ];

                        $totalAmount += $totalPrice;
                    }
                });

            $requisition = Requisition::create([
                'member_id' => $member->id,
                'accounting_event_id' => $this->accountingEvent->id,
                'requisition_date' => now(),
                'responsible_desk' => $this->accountingEvent->responsible_desk,
                'total_amount' => $totalAmount,
            ]);

            foreach ($items as $item) {
                $requisition->requisitionItems()->create($item);
            }
        });
    }

    /**
     * Scale quantities of per-person expense categories to the expected headcount.
     */
    private function scaledQuantity(
        BudgetEstimateEntry $entry,
        BudgetEstimate $budgetEstimate,
        Mission $mission,
    ): int {
        $baseQuantity = max(1, (int) $entry->quantity);

        if (! $entry->expenseCategory?->is_per_person) {
            return $baseQuantity;
        }

        $baseline = max(1, (int) $budgetEstimate->baseline_people);
        $expected = $this->expectedPeople($mission, $budgetEstimate);

        return max(1, (int) round($baseQuantity * $expected / $baseline));
    }

    /**
     * Expected number of people going on the mission:
     * current subscription count, falling back to the school's default team
     * size for this mission type, then the estimate's baseline headcount.
     */
    private function expectedPeople(Mission $mission, BudgetEstimate $budgetEstimate): int
    {
        $subscribed = $mission->missionSubscriptions()
            ->where('status', '!=', PRFMissionSubscriptionStatus::WITHDRAWN->value)
            ->count();

        if ($subscribed > 0) {
            return $subscribed;
        }

        $defaultCapacity = $mission->school?->getMissionDefaults($mission->mission_type_id)['default_capacity'];

        if (filled($defaultCapacity)) {
            return max(1, (int) $defaultCapacity);
        }

        return max(1, (int) $budgetEstimate->baseline_people);
    }
}
