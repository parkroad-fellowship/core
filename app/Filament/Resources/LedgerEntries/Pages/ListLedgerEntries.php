<?php

namespace App\Filament\Resources\LedgerEntries\Pages;

use App\Enums\PRFFinancialReportType;
use App\Filament\Resources\LedgerEntries\LedgerEntryResource;
use App\Jobs\FinancialReport\CreateJob as CreateFinancialReportJob;
use App\Models\AccountTransfer;
use App\Models\FinancialReport;
use App\Models\LedgerEntry;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListLedgerEntries extends ListRecords
{
    protected static string $resource = LedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        $transferUrl = class_exists('App\Filament\Resources\AccountTransfers\AccountTransferResource')
            ? \App\Filament\Resources\AccountTransfers\AccountTransferResource::getUrl('create')
            : null;

        return [
            Action::make('receipt_income')
                ->label('Receipt income')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->url(fn(): string => LedgerEntryResource::getUrl('receipt'))
                ->visible(fn(): bool => userCan(LedgerEntry::permission('create'))),

            Action::make('record_payment')
                ->label('Record payment')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('danger')
                ->url(fn(): string => LedgerEntryResource::getUrl('pay'))
                ->visible(fn(): bool => userCan(LedgerEntry::permission('create'))),

            Action::make('transfer')
                ->label('Transfer')
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray')
                ->url(fn(): string => $transferUrl)
                ->visible(fn(): bool => $transferUrl !== null && userCan(AccountTransfer::permission('create'))),

            Action::make('export')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->schema([
                    DatePicker::make('from')->label('From')->required()->default(now()->startOfMonth())->native(false),
                    DatePicker::make('to')->label('To')->required()->default(now()->endOfMonth())->native(false),
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
                        ->title('Cashbook export started')
                        ->body(
                            "Generating the cashbook from {$data['from']} to {$data['to']}. You'll get an email when it's ready.",
                        )
                        ->send();
                })
                ->visible(fn(): bool => LedgerEntryResource::canGenerateReport()),
        ];
    }

    public function getTitle(): string
    {
        return 'Cashbook';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(LedgerEntry::permission('viewAny'));
    }
}
