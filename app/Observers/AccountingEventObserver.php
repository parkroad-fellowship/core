<?php

namespace App\Observers;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFMorphType;
use App\Helpers\Utils;
use App\Models\AccountingEvent;
use App\Models\BudgetEstimate;
use App\Models\BudgetEstimateEntry;
use App\Models\Member;
use App\Models\Mission;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\School;
use Illuminate\Support\Facades\DB;

class AccountingEventObserver
{
    /**
     * Handle the AccountingEvent "created" event.
     */
    public function created(AccountingEvent $accountingEvent): void
    {
        // If this is a mission, create a default requisition
        if ($accountingEvent->accounting_eventable_type === PRFMorphType::MISSION) {
            $activeBudgetEstimate = BudgetEstimate::query()
                ->where([
                    'is_active' => PRFActiveStatus::ACTIVE->value,
                    'budget_estimatable_type' => PRFMorphType::SCHOOL->value,
                    'budget_estimatable_id' => School::query()
                        ->where(
                            'id',
                            Mission::query()
                                ->where('id', $accountingEvent->accounting_eventable_id)
                                ->select('school_id')
                                ->limit(1))
                        ->select('id')
                        ->limit(1),
                ])
                ->first();

            if (! $activeBudgetEstimate) {
                return;
            }

            // Create a default requisition
            $member = Member::query() // Get desk head email
                ->whereEmail(Utils::getDeskEmails($accountingEvent->responsible_desk)[0])
                ->firstOrFail();

            DB::transaction(function () use ($member, $accountingEvent, $activeBudgetEstimate) {
                $requisition = Requisition::create([
                    'member_id' => $member->id,
                    'accounting_event_id' => $accountingEvent->id,
                    'requisition_date' => now(),
                    'responsible_desk' => $accountingEvent->responsible_desk,
                    'name' => $accountingEvent->name,
                ]);

                BudgetEstimateEntry::query()
                    ->where('budget_estimate_id', $activeBudgetEstimate->id)
                    ->chunk(10, function ($budgetEstimateEntries) use ($requisition) {
                        foreach ($budgetEstimateEntries as $budgetEstimateEntry) {
                            RequisitionItem::create([
                                'requisition_id' => $requisition->id,
                                'expense_category_id' => $budgetEstimateEntry->expense_category_id,
                                'item_name' => $budgetEstimateEntry->item_name,
                                'description' => $budgetEstimateEntry->notes ?? 'N/A',
                                'unit_price' => $budgetEstimateEntry->unit_price,
                                'quantity' => $budgetEstimateEntry->quantity,
                                'total_price' => $budgetEstimateEntry->total_price,
                            ]);
                        }
                    });
            });
        }
    }

    /**
     * Handle the AccountingEvent "updated" event.
     */
    public function updated(AccountingEvent $accountingEvent): void
    {
        //
    }

    /**
     * Handle the AccountingEvent "deleted" event.
     */
    public function deleted(AccountingEvent $accountingEvent): void
    {
        //
    }

    /**
     * Handle the AccountingEvent "restored" event.
     */
    public function restored(AccountingEvent $accountingEvent): void
    {
        //
    }

    /**
     * Handle the AccountingEvent "force deleted" event.
     */
    public function forceDeleted(AccountingEvent $accountingEvent): void
    {
        //
    }
}
