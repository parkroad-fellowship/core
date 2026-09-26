<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AccountTransfers\AccountTransferResource;
use App\Filament\Resources\FinancialReports\FinancialReportResource;
use App\Filament\Resources\LedgerEntries\LedgerEntryResource;
use App\Filament\Widgets\AccountBalancesOverview;
use App\Filament\Widgets\BudgetUtilizationChart;
use App\Filament\Widgets\ExpensesByCategoryChart;
use App\Filament\Widgets\GiftsDonationsWidget;
use App\Filament\Widgets\IncomeVsExpenseChart;
use App\Filament\Widgets\PaymentMethodsChart;
use App\Filament\Widgets\RequisitionsAwaitingDisbursementWidget;
use App\Filament\Widgets\RequisitionStatusWidget;
use Filament\Actions\Action;
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
            AccountBalancesOverview::class,
            IncomeVsExpenseChart::class,
            ExpensesByCategoryChart::class,
            GiftsDonationsWidget::class,
            RequisitionsAwaitingDisbursementWidget::class,
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

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('receipt_income')
                ->label('Receipt income')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(fn(): ?string => class_exists(LedgerEntryResource::class) ? LedgerEntryResource::getUrl() : null)
                ->disabled(fn(): bool => !class_exists(LedgerEntryResource::class))
                ->tooltip('Receipt income in the cashbook'),

            Action::make('record_payment')
                ->label('Record payment')
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->url(fn(): ?string => class_exists(LedgerEntryResource::class) ? LedgerEntryResource::getUrl() : null)
                ->disabled(fn(): bool => !class_exists(LedgerEntryResource::class))
                ->tooltip('Record a payment in the cashbook'),

            Action::make('transfer')
                ->label('Transfer')
                ->icon('heroicon-o-arrows-right-left')
                ->color('info')
                ->url(fn(): string => AccountTransferResource::getUrl('create')),

            Action::make('generate_report')
                ->label('Generate report')
                ->icon('heroicon-o-document-chart-bar')
                ->color('primary')
                ->url(fn(): string => FinancialReportResource::getUrl()),
        ];
    }
}
