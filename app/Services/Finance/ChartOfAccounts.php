<?php

namespace App\Services\Finance;

use App\Enums\PRFFinancialAccountType;
use App\Enums\PRFResponsibleDesk;
use App\Models\FinancialAccount;
use App\Models\LedgerCategory;
use RuntimeException;

/**
 * Finds the accounts and categories the app posts to automatically (see config/prf/finance.php).
 */
class ChartOfAccounts
{
    public function category(string $code): LedgerCategory
    {
        return (
            LedgerCategory::query()->where('code', $code)->first() ?? throw new RuntimeException(
                "Ledger category [{$code}] is missing. Run prf:tenants:seed-reference-data.",
            )
        );
    }

    public function expenseFor(PRFResponsibleDesk $desk): LedgerCategory
    {
        return $this->category('expense.desk.' . $desk->value);
    }

    public function refundFor(PRFResponsibleDesk $desk): LedgerCategory
    {
        return $this->category('refund.desk.' . $desk->value);
    }

    public function transactionCosts(): LedgerCategory
    {
        return $this->category('charge.transaction_costs');
    }

    public function transfer(): LedgerCategory
    {
        return $this->category('transfer');
    }

    public function openingBalance(): LedgerCategory
    {
        return $this->category('opening_balance');
    }

    /**
     * The first active account of a type (e.g. where Paystack gifts land).
     */
    public function account(PRFFinancialAccountType $type): FinancialAccount
    {
        return (
            FinancialAccount::query()
                ->active()
                ->where('type', $type)
                ->orderBy('id')
                ->first() ?? throw new RuntimeException(
                "No active {$type->getLabel()} account. Add one under Treasurer → Accounts.",
            )
        );
    }
}
