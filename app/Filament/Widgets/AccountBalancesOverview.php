<?php

namespace App\Filament\Widgets;

use App\Models\FinancialAccount;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Where the fellowship's money is right now: one tile per account and the total.
 */
class AccountBalancesOverview extends BaseWidget
{
    protected static ?int $sort = -10;

    protected ?string $heading = 'Account balances';

    protected ?string $description = 'Live balances from the cashbook.';

    protected function getColumns(): int
    {
        return 4;
    }

    public static function canView(): bool
    {
        return (bool) Auth::user()?->can(FinancialAccount::permission('viewAny'));
    }

    protected function getStats(): array
    {
        $accounts = FinancialAccount::query()
            ->active()
            ->withBalance()
            ->withMax('ledgerEntries as last_entry_on', 'transacted_on')
            ->orderBy('name')
            ->get();

        $stats = $accounts->map(fn(FinancialAccount $account) => Stat::make(
            $account->name,
            'KES ' . number_format($account->balance),
        )
            ->description(
                $account->last_entry_on !== null
                    ? 'Last entry ' . Carbon::parse($account->last_entry_on)->format('M j, Y')
                    : 'No entries yet',
            )
            ->descriptionIcon($account->type->getIcon())
            ->color($account->balance < 0 ? 'danger' : $account->type->getColor()))->all();

        $stats[] = Stat::make(
            'Total across accounts',
            'KES ' . number_format($accounts->sum(fn(FinancialAccount $account) => $account->balance)),
        )
            ->description($accounts->count() . ' active accounts')
            ->descriptionIcon('heroicon-o-banknotes')
            ->color('primary');

        return $stats;
    }
}
