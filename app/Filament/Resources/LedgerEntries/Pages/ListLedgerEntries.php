<?php

namespace App\Filament\Resources\LedgerEntries\Pages;

use App\Enums\PRFFinancialReportType;
use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFLedgerFlow;
use App\Filament\Resources\AccountTransfers\AccountTransferResource;
use App\Filament\Resources\LedgerEntries\LedgerEntryResource;
use App\Filament\Widgets\AccountBalancesOverview;
use App\Jobs\FinancialReport\CreateJob as CreateFinancialReportJob;
use App\Models\AccountTransfer;
use App\Models\LedgerEntry;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ListLedgerEntries extends ListRecords
{
    protected static string $resource = LedgerEntryResource::class;

    public function getTitle(): string
    {
        return 'Cashbook';
    }

    public function getSubheading(): ?string
    {
        return 'Every shilling in or out of the fellowship’s accounts. Receipt money as it comes in, record payments as they go out, and share receipts with givers.';
    }

    protected function getHeaderWidgets(): array
    {
        return [AccountBalancesOverview::class];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'in' => Tab::make('Money in')
                ->icon('heroicon-m-arrow-down-left')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('flow', PRFLedgerFlow::RECEIPT)->ofKind(
                        PRFLedgerCategoryKind::INCOME,
                        PRFLedgerCategoryKind::REFUND,
                    ),
                ),
            'out' => Tab::make('Money out')
                ->icon('heroicon-m-arrow-up-right')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('flow', PRFLedgerFlow::PAYMENT)->ofKind(
                        PRFLedgerCategoryKind::EXPENSE,
                        PRFLedgerCategoryKind::CHARGE,
                    ),
                ),
            'transfers' => Tab::make('Transfers')
                ->icon('heroicon-m-arrows-right-left')
                ->modifyQueryUsing(fn(Builder $query) => $query->ofKind(PRFLedgerCategoryKind::TRANSFER)),
            'opening' => Tab::make('Opening balances')->modifyQueryUsing(
                fn(Builder $query) => $query->ofKind(PRFLedgerCategoryKind::OPENING_BALANCE),
            ),
        ];
    }

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

            ActionGroup::make([
                Action::make('transfer')
                    ->label('Move money between accounts')
                    ->icon('heroicon-o-arrows-right-left')
                    ->url(fn(): string => AccountTransferResource::getUrl('create'))
                    ->visible(fn(): bool => userCan(AccountTransfer::permission('create'))),

                Action::make('export')
                    ->label('Download as Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->modalHeading('Download the cashbook')
                    ->modalDescription(
                        'We’ll build the workbook (a sheet per account, cash balances, income statement and treasurer report) and email it to you. It will also be under Financial Reports.',
                    )
                    ->modalSubmitActionLabel('Generate')
                    ->fillForm(fn(): array => $this->filteredPeriod())
                    ->schema([
                        DatePicker::make('from')->label('From')->required()->native(false),
                        DatePicker::make('to')->label('To')->required()->afterOrEqual('from')->native(false),
                    ])
                    ->action(function (array $data): void {
                        CreateFinancialReportJob::dispatchSync([
                            'type' => PRFFinancialReportType::CASHBOOK->value,
                            'period_start' => $data['from'],
                            'period_end' => $data['to'],
                            'requested_by' => Auth::id(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Generating the cashbook')
                            ->body('You’ll get an email with the workbook shortly.')
                            ->send();
                    })
                    ->visible(fn(): bool => LedgerEntryResource::canGenerateReport()),
            ])
                ->label('More')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->button(),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(LedgerEntry::permission('viewAny'));
    }

    /**
     * The date range the treasurer is looking at, so an export matches the screen.
     *
     * @return array{from: string, to: string}
     */
    private function filteredPeriod(): array
    {
        $range = $this->tableFilters['transacted_on'] ?? [];

        return [
            'from' => filled($range['from'] ?? null)
                ? Carbon::parse($range['from'])->toDateString()
                : now()->startOfMonth()->toDateString(),
            'to' => filled($range['to'] ?? null) ? Carbon::parse($range['to'])->toDateString() : now()->toDateString(),
        ];
    }
}
