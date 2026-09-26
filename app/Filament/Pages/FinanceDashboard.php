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
use App\Models\AccountTransfer;
use App\Models\FinancialAccount;
use App\Models\FinancialReport;
use App\Models\LedgerEntry;
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

    public static function canAccess(): bool
    {
        return userCan(FinancialAccount::permission('viewAny'));
    }

    public function getSubheading(): ?string
    {
        return 'Where the fellowship’s money is, what came in and went out, and what needs your attention.';
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('receipt_income')
                ->label('Receipt income')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->url(fn(): string => LedgerEntryResource::getUrl('receipt'))
                ->visible(fn(): bool => userCan(LedgerEntry::permission('create'))),

            Action::make('record_payment')
                ->label('Record payment')
                ->icon('heroicon-o-arrow-up-right')
                ->color('danger')
                ->url(fn(): string => LedgerEntryResource::getUrl('pay'))
                ->visible(fn(): bool => userCan(LedgerEntry::permission('create'))),

            Action::make('transfer')
                ->label('Transfer')
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray')
                ->url(fn(): string => AccountTransferResource::getUrl('create'))
                ->visible(fn(): bool => userCan(AccountTransfer::permission('create'))),

            Action::make('generate_report')
                ->label('Reports')
                ->icon('heroicon-o-document-chart-bar')
                ->color('gray')
                ->url(fn(): string => FinancialReportResource::getUrl())
                ->visible(fn(): bool => userCan(FinancialReport::permission('viewAny'))),
        ];
    }
}
