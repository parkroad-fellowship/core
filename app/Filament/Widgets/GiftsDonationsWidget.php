<?php

namespace App\Filament\Widgets;

use App\Services\Finance\FinancialStatements;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GiftsDonationsWidget extends BaseWidget
{
    protected static ?int $sort = 21;

    /**
     * Income by statement line this year, read from the ledger (receipted income only,
     * so pending or failed online payments never count).
     */
    protected function getStats(): array
    {
        $statement = app(FinancialStatements::class)->incomeStatement(now()->copy()->startOfYear(), now());
        $receipts = collect($statement['receipts'])->filter(fn(int $total): bool => $total > 0)->sortDesc();

        $total = (int) array_sum($statement['receipts']);

        $stats = [
            Stat::make('Income this year', 'KES ' . number_format($total))
                ->description('Receipted income, all lines')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];

        $colors = ['primary', 'info', 'warning'];

        $index = 0;

        foreach ($receipts->take(3) as $line => $amount) {
            $share = $total > 0 ? round(($amount / $total) * 100) : 0;

            $stats[] = Stat::make($line, 'KES ' . number_format($amount))
                ->description($share . '% of income')
                ->descriptionIcon('heroicon-m-gift')
                ->color($colors[$index] ?? 'gray');

            $index++;
        }

        return $stats;
    }
}
