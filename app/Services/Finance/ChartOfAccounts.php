<?php

namespace App\Services\Finance;

use App\Enums\PRFFinancialAccountType;
use App\Enums\PRFResponsibleDesk;
use App\Models\FinancialAccount;
use App\Models\LedgerCategory;
use Database\Seeders\FinancialAccountSeeder;
use Database\Seeders\LedgerCategorySeeder;
use RuntimeException;

/**
 * Finds the accounts and categories the app posts to automatically (see config/prf/finance.php).
 */
class ChartOfAccounts
{
    private bool $seeded = false;

    public function category(string $code): LedgerCategory
    {
        $find = fn(): ?LedgerCategory => LedgerCategory::query()->where('code', $code)->first();

        return (
            $find() ?? $this->seedChart($find) ?? throw new RuntimeException(
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
        $find = fn(): ?FinancialAccount => FinancialAccount::query()
            ->active()
            ->where('type', $type)
            ->orderBy('id')
            ->first();

        return (
            $find() ?? $this->seedChart($find) ?? throw new RuntimeException(
                "No active {$type->getLabel()} account. Add one under Treasurer → Accounts.",
            )
        );
    }

    /**
     * Tenants created before the treasurer's ledger existed may not have the chart yet: seed it
     * (idempotently) the first time the app needs it, then look again. Seeding never overwrites
     * the treasurer's renames and never re-activates an account they closed.
     *
     * @template TModel
     *
     * @param  callable(): (TModel|null)  $find
     * @return TModel|null
     */
    private function seedChart(callable $find): mixed
    {
        if ($this->seeded) {
            return null;
        }

        $this->seeded = true;

        new FinancialAccountSeeder()->run();
        new LedgerCategorySeeder()->run();

        return $find();
    }
}
