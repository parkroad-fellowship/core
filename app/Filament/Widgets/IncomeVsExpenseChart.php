<?php

namespace App\Filament\Widgets;

use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFLedgerFlow;
use App\Models\LedgerEntry;
use Filament\Widgets\ChartWidget;

class IncomeVsExpenseChart extends ChartWidget
{
    protected ?string $heading = 'Income vs Expenses';

    protected static ?int $sort = 7;

    protected function getData(): array
    {
        $months = [];
        $incomeData = [];
        $expenseData = [];

        // Monthly income (INCOME receipts) vs expenditure (EXPENSE and CHARGE
        // payments minus REFUND receipts, which reduce their desk's expense).
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');

            $base = LedgerEntry::query()
                ->whereYear('transacted_on', $date->year)
                ->whereMonth('transacted_on', $date->month);

            $incomeData[] = (clone $base)
                ->where('flow', PRFLedgerFlow::RECEIPT)
                ->ofKind(PRFLedgerCategoryKind::INCOME)
                ->sum('amount');

            $payments = (clone $base)
                ->where('flow', PRFLedgerFlow::PAYMENT)
                ->ofKind(PRFLedgerCategoryKind::EXPENSE, PRFLedgerCategoryKind::CHARGE)
                ->sum('amount');

            $refunds = (clone $base)
                ->where('flow', PRFLedgerFlow::RECEIPT)
                ->ofKind(PRFLedgerCategoryKind::REFUND)
                ->sum('amount');

            $expenseData[] = $payments - $refunds;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Income (KES)',
                    'data' => $incomeData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
                [
                    'label' => 'Expenses (KES)',
                    'data' => $expenseData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
