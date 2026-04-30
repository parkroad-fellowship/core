<?php

namespace App\Filament\Widgets;

use App\Enums\PRFEntryType;
use App\Models\AllocationEntry;
use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class IncomeVsExpenseChart extends ChartWidget
{
    protected ?string $heading = 'Income vs Expenses';

    protected static ?int $sort = 7;

    protected function getData(): array
    {
        $months = collect();
        $incomeData = collect();
        $expenseData = collect();

        // Get data for the last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push($date->format('M Y'));

            $incomeData->push(
                Payment::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('amount') ?? 0
            );

            $expenseData->push(
                AllocationEntry::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('entry_type', PRFEntryType::DEBIT->value)
                    ->sum('amount') ?? 0
            );
        }

        return [
            'datasets' => [
                [
                    'label' => 'Income (KES)',
                    'data' => $incomeData->toArray(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
                [
                    'label' => 'Expenses (KES)',
                    'data' => $expenseData->toArray(),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
