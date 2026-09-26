<?php

namespace App\Filament\Widgets;

use App\Enums\PRFEntryType;
use App\Filament\Support\PRFPalette;
use App\Models\AllocationEntry;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ExpensesByCategoryChart extends ChartWidget
{
    protected ?string $heading = 'Monthly Expenses by Category';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $expenseData = AllocationEntry::select('expense_category_id', DB::raw('sum(amount) as total'))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereNotNull('expense_category_id')
            ->groupBy('expense_category_id')
            ->with('expenseCategory')
            ->where('entry_type', PRFEntryType::DEBIT)
            ->get();

        $labels = [];
        $data = [];

        foreach ($expenseData as $item) {
            $labels[] = $item->expenseCategory->name ?? 'Unknown';
            $data[] = $item->total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Amount (KES)',
                    'data' => $data,
                    'backgroundColor' => PRFPalette::SERIES,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
