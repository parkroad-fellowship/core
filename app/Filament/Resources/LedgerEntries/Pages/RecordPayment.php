<?php

namespace App\Filament\Resources\LedgerEntries\Pages;

use App\Filament\Resources\LedgerEntries\LedgerEntryResource;
use App\Helpers\Utils;
use App\Jobs\LedgerEntry\CreateJob;
use App\Models\AccountingEvent;
use App\Models\LedgerEntry;
use App\Services\Finance\ChartOfAccounts;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

/**
 * Records money out: expenses and transaction charges. Payments never get receipt numbers.
 */
class RecordPayment extends CreateRecord
{
    protected static string $resource = LedgerEntryResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payment')
                ->columnSpanFull()
                ->description(
                    'Money paid out of an account. Paying out an approved requisition? Use “Record disbursement” on the requisition instead, so it is linked to its mission or event.',
                )
                ->icon('heroicon-o-arrow-up-tray')
                ->schema([
                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            DatePicker::make('transacted_on')
                                ->label('Date paid')
                                ->required()
                                ->default(now())
                                ->maxDate(now())
                                ->native(false),

                            Select::make('financial_account_ulid')
                                ->label('Paid from')
                                ->options(fn(): array => LedgerEntryResource::accountOptions())
                                ->required()
                                ->searchable()
                                ->preload()
                                ->native(false),

                            Select::make('ledger_category_ulid')
                                ->label('Category')
                                ->options(fn(): array => LedgerEntryResource::paymentCategoryOptions())
                                ->required()
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->helperText('Expense and charge categories, grouped by desk.')
                                ->columnSpanFull(),

                            TextInput::make('amount')
                                ->label('Amount')
                                ->required()
                                ->integer()
                                ->minValue(1)
                                ->prefix('KES')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn($state, Set $set) => $set(
                                    'charge',
                                    Utils::estimateTransferCharge((int) $state),
                                )),

                            TextInput::make('counterparty')
                                ->label('Payee')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Who was paid?'),

                            TextInput::make('reference')
                                ->label('Reference')
                                ->maxLength(255)
                                ->placeholder('M-Pesa code, bank slip or cheque no.')
                                ->columnSpanFull(),

                            Select::make('accounting_event_ulid')
                                ->label('Accounting event (optional)')
                                ->options(
                                    fn(): array => AccountingEvent::query()
                                        ->orderByDesc('due_date')
                                        ->limit(100)
                                        ->pluck('name', 'ulid')
                                        ->all(),
                                )
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->columnSpanFull(),

                            Textarea::make('description')->label('Description')->rows(2)->columnSpanFull(),

                            TextInput::make('charge')
                                ->label('Transaction charge')
                                ->integer()
                                ->minValue(0)
                                ->default(0)
                                ->prefix('KES')
                                ->helperText(
                                    'Estimated from the M-Pesa tariff when you enter the amount. Change it to what was actually charged, or 0 for none. It is booked as a separate Treasurer’s Desk charge.',
                                )
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $charge = (int) Arr::pull($data, 'charge', 0);

        $entry = CreateJob::dispatchSync([...$data, 'recorded_by' => Auth::id()]);

        if ($charge > 0) {
            CreateJob::dispatchSync([
                'financial_account_ulid' => $data['financial_account_ulid'],
                'ledger_category_ulid' => app(ChartOfAccounts::class)->transactionCosts()->ulid,
                'amount' => $charge,
                'transacted_on' => $data['transacted_on'] ?? now()->format('Y-m-d'),
                'counterparty' => $data['counterparty'] ?? null,
                'description' => 'Transaction charge for ' . ($data['description'] ?? $data['reference'] ?? 'payment'),
                'reference' => $data['reference'] ?? null,
                'accounting_event_ulid' => $data['accounting_event_ulid'] ?? null,
                'recorded_by' => Auth::id(),
            ]);
        }

        return $entry;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        /** @var LedgerEntry $entry */
        $entry = $this->getRecord();

        return Notification::make()
            ->success()
            ->title('Payment recorded')
            ->body(
                'KES '
                . number_format($entry->amount)
                . ' paid from '
                . ($entry->financialAccount?->name ?? 'the account')
                . '.',
            )
            ->actions([
                Action::make('record_another')->label('Record another')->url(LedgerEntryResource::getUrl('pay')),
            ]);
    }

    public function getSubheading(): ?string
    {
        return 'Record money that left one of the fellowship’s accounts, and which desk it was for.';
    }

    public function getTitle(): string
    {
        return 'Record payment';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(LedgerEntry::permission('create'));
    }
}
