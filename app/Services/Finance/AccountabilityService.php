<?php

namespace App\Services\Finance;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFEntryType;
use App\Enums\PRFResponsibleDesk;
use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use App\Models\ExpenseCategory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The monthly accountability sheet: every accounting event (missions and PRF Events of every
 * desk) with its disbursement, real expenses by category, tokens, refunds and balance.
 */
class AccountabilityService
{
    /**
     * @return Collection<int, AccountabilityRow>
     */
    public function forMonth(Carbon $month, ?PRFResponsibleDesk $desk = null): Collection
    {
        return $this->between($month->copy()->startOfMonth(), $month->copy()->endOfMonth(), $desk);
    }

    /**
     * @return Collection<int, AccountabilityRow>
     */
    public function between(Carbon $from, Carbon $to, ?PRFResponsibleDesk $desk = null): Collection
    {
        return AccountingEvent::query()
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->when($desk, fn($query) => $query->where('responsible_desk', $desk))
            ->with(['allocationEntries', 'refunds', 'accountingEventable'])
            ->orderBy('due_date')
            ->get()
            ->map(fn(AccountingEvent $event) => $this->row($event));
    }

    public function row(AccountingEvent $event): AccountabilityRow
    {
        $entries = $event->allocationEntries;

        $credits = $entries->where('entry_type', PRFEntryType::CREDIT);
        $debits = $entries->where('entry_type', PRFEntryType::DEBIT);

        // A recalled requisition is reversed by an uncategorised DEBIT on the same requisition.
        $reversals = $debits->filter(
            fn(AllocationEntry $entry) => $entry->expense_category_id === null && $entry->requisition_id !== null,
        );
        $spending = $debits->reject(fn(AllocationEntry $entry) => $reversals->contains($entry));

        $expenses = $spending
            ->groupBy('expense_category_id')
            ->map(fn(Collection $group) => (int) $group->sum(
                fn(AllocationEntry $entry) => $entry->amount - (int) $entry->charge,
            ))
            ->all();

        return new AccountabilityRow(
            event: $event,
            disbursed: (int) $credits->where('is_token_of_appreciation', false)->sum('amount')
            - (int) $reversals->sum('amount'),
            expenses: $expenses,
            transactionCosts: (int) $spending->sum('charge'),
            tokens: (int) $credits->where('is_token_of_appreciation', true)->sum('amount'),
            refunded: (int) $event->refunds->sum('amount'),
        );
    }

    /**
     * Expense categories to show as columns: every active category plus any used by these rows.
     *
     * @param  Collection<int, AccountabilityRow>  $rows
     * @return Collection<int, ExpenseCategory>
     */
    public function expenseColumns(Collection $rows): Collection
    {
        $used = $rows
            ->flatMap(fn(AccountabilityRow $row) => array_keys($row->expenses))
            ->filter()
            ->unique();

        return ExpenseCategory::query()
            ->where(fn($query) => $query->where('is_active', PRFActiveStatus::ACTIVE)->orWhereIn('id', $used))
            ->orderBy('id')
            ->get();
    }
}
