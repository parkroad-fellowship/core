<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BudgetUtilizationChart;
use App\Filament\Widgets\ExpensesByCategoryChart;
use App\Filament\Widgets\GiftsDonationsWidget;
use App\Filament\Widgets\IncomeVsExpenseChart;
use App\Filament\Widgets\PaymentMethodsChart;
use App\Filament\Widgets\RequisitionStatusWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class FinanceDashboard extends BaseDashboard
{
    protected static ?string $title = 'Financial Stewardship';

    protected static string $routePath = 'financial-stewardship';

    protected static string|\UnitEnum|null $navigationGroup = 'Treasurer';

    protected static ?string $navigationLabel = 'Financial Stewardship';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            IncomeVsExpenseChart::class,
            ExpensesByCategoryChart::class,
            GiftsDonationsWidget::class,
            RequisitionStatusWidget::class,
            BudgetUtilizationChart::class,
            PaymentMethodsChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
