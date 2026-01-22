<?php

namespace App\Filament\Widgets;

use App\Enums\PRFEntryType;
use App\Models\AllocationEntry;
use App\Models\BudgetEstimateEntry;
use App\Models\ExpenseCategory;
use Filament\Widgets\ChartWidget;

class BudgetUtilizationChart extends ChartWidget
{
    protected ?string $heading = 'Budget vs Actual Spending';

    protected static ?int $sort = 15;

    protected function getData(): array
    {
        $currentYear = now()->year;

        // Get top expense categories with budgets and actuals
        $categories = ExpenseCategory::query()
            ->limit(6)
            ->get();

        $labels = [];
        $budgetData = [];
        $actualData = [];

        foreach ($categories as $category) {
            $labels[] = strlen($category->name) > 15
                ? substr($category->name, 0, 12).'...'
                : $category->name;

            // Get budget estimate for this category
            $budget = BudgetEstimateEntry::query()
                ->where('expense_category_id', $category->id)
                ->whereHas('budgetEstimate', function ($query) use ($currentYear) {
                    $query->whereYear('created_at', $currentYear);
                })
                ->sum('total_price') ?? 0;

            $budgetData[] = $budget;

            // Get actual spending for this category
            $actual = AllocationEntry::query()
                ->where('expense_category_id', $category->id)
                ->where('entry_type', PRFEntryType::DEBIT->value)
                ->whereYear('created_at', $currentYear)
                ->sum('amount') ?? 0;

            $actualData[] = $actual;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Budget (KES)',
                    'data' => $budgetData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => 'Actual (KES)',
                    'data' => $actualData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                    'borderColor' => 'rgb(239, 68, 68)',
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
