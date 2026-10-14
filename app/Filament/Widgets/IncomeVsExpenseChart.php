<?php

namespace App\Filament\Widgets;

use App\Services\Finance\FinancialStatements;
use Filament\Widgets\ChartWidget;

class IncomeVsExpenseChart extends ChartWidget
{
    protected ?string $heading = 'Income vs Expenses';

    protected static ?int $sort = 7;

    protected function getData(): array
    {
        $statements = app(FinancialStatements::class);
        $months = [];
        $incomeData = [];
        $expenseData = [];

        // Same rules as the income statement: refunds reduce expenses, transfers and opening
        // balances are left out.
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonthsNoOverflow($i);
            $statement = $statements->incomeStatement($month->copy(), $month->copy()->endOfMonth());

            $months[] = $month->format('M Y');
            $incomeData[] = array_sum($statement['receipts']);
            $expenseData[] = array_sum($statement['expenditure']);
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
