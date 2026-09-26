<?php

namespace App\Filament\Resources\LedgerEntries\Pages;

use App\Filament\Resources\LedgerEntries\LedgerEntryResource;
use App\Helpers\Utils;
use App\Jobs\LedgerEntry\CreateJob;
use App\Models\AccountingEvent;
use App\Models\LedgerEntry;
use App\Services\Finance\ChartOfAccounts;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                ->description('Money paid out of an account')
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
                                ->label('Amount (KES)')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->step(1)
                                ->prefix('KES')
                                ->live(),

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

                            Checkbox::make('record_charge')
                                ->label(
                                    fn(Get $get): string => (
                                        'Also record the M-Pesa charge of KES '
                                        . number_format(Utils::estimateTransferCharge((int) ($get('amount') ?? 0)))
                                        . ' as a second line'
                                    ),
                                )
                                ->helperText('Posts a transaction-costs line on the same account and date.')
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
        $recordCharge = (bool) Arr::pull($data, 'record_charge', false);

        $entry = CreateJob::dispatchSync([...$data, 'recorded_by' => Auth::id()]);

        if ($recordCharge) {
            $charge = Utils::estimateTransferCharge($entry->amount);

            if ($charge > 0) {
                CreateJob::dispatchSync([
                    'financial_account_ulid' => $data['financial_account_ulid'],
                    'ledger_category_ulid' => app(ChartOfAccounts::class)->transactionCosts()->ulid,
                    'amount' => $charge,
                    'transacted_on' => $data['transacted_on'] ?? now()->format('Y-m-d'),
                    'counterparty' => $data['counterparty'] ?? null,
                    'description' =>
                        'Transaction charge for ' . ($data['description'] ?? $data['reference'] ?? 'payment'),
                    'reference' => $data['reference'] ?? null,
                    'accounting_event_ulid' => $data['accounting_event_ulid'] ?? null,
                    'recorded_by' => Auth::id(),
                ]);
            }
        }

        return $entry;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Payment recorded')
            ->body('The payment has been posted to the cashbook.');
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
