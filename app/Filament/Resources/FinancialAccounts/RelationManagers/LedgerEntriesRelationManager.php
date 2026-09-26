<?php

namespace App\Filament\Resources\FinancialAccounts\RelationManagers;

use App\Enums\PRFLedgerFlow;
use App\Models\FinancialAccount;
use App\Models\LedgerCategory;
use App\Models\LedgerEntry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The account's cashbook: every line in and out, with a running balance. Read-only;
 * corrections go through the Cashbook screen so auto-posted lines stay consistent.
 */
class LedgerEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'ledgerEntries';

    protected static ?string $title = 'Cashbook';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public function table(Table $table): Table
    {
        return $table
            // The balance after each line is computed from the whole account history, so it stays
            // right whatever filters, search or sort the treasurer applies.
            ->modifyQueryUsing(
                fn(Builder $query): Builder => $query
                    ->select('ledger_entries.*')
                    ->selectRaw('(SELECT COALESCE(SUM(CASE WHEN history.flow = ? THEN history.amount ELSE -history.amount END), 0)
                          FROM ledger_entries AS history
                          WHERE history.financial_account_id = ledger_entries.financial_account_id
                            AND history.tenant_id = ledger_entries.tenant_id
                            AND history.deleted_at IS NULL
                            AND (history.transacted_on < ledger_entries.transacted_on
                                 OR (history.transacted_on = ledger_entries.transacted_on AND history.id <= ledger_entries.id))
                         ) AS running_balance', [PRFLedgerFlow::RECEIPT->value])
                    ->with('ledgerCategory'),
            )
            ->defaultSort(
                fn(Builder $query): Builder => $query
                    ->orderByDesc('ledger_entries.transacted_on')
                    ->orderByDesc('ledger_entries.id'),
            )
            ->columns([
                TextColumn::make('transacted_on')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->tooltip('When the money moved'),

                TextColumn::make('counterparty')
                    ->label('Received From / Paid To')
                    ->searchable()
                    ->weight('medium')
                    ->placeholder('—')
                    ->tooltip('Who gave or received the money'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->wrap()
                    ->placeholder('—')
                    ->tooltip(fn(LedgerEntry $record): ?string => $record->description),

                TextColumn::make('receipt_reference')
                    ->label('Receipt No. / Reference')
                    ->state(fn(LedgerEntry $record): ?string => $record->receipt_number ?? $record->reference)
                    ->copyable()
                    ->toggleable()
                    ->placeholder('—')
                    ->tooltip('Official receipt number for income, otherwise the M-Pesa code, bank slip or cheque no.'),

                TextColumn::make('receipt_amount')
                    ->label('Receipts (KES)')
                    ->state(fn(LedgerEntry $record): ?int => $record->flow === PRFLedgerFlow::RECEIPT
                        ? $record->amount
                        : null)
                    ->money('KES', divideBy: 1)
                    ->color('success')
                    ->placeholder('—')
                    ->tooltip('Money into the account'),

                TextColumn::make('payment_amount')
                    ->label('Payments (KES)')
                    ->state(fn(LedgerEntry $record): ?int => $record->flow === PRFLedgerFlow::PAYMENT
                        ? $record->amount
                        : null)
                    ->money('KES', divideBy: 1)
                    ->color('danger')
                    ->placeholder('—')
                    ->tooltip('Money out of the account'),

                TextColumn::make('ledgerCategory.name')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->description(
                        fn(LedgerEntry $record): ?string => $record->ledgerCategory?->responsible_desk?->getLabel(),
                    )
                    ->tooltip('What the money was for'),

                TextColumn::make('running_balance')
                    ->label('Balance (KES)')
                    ->money('KES', divideBy: 1)
                    ->weight('semibold')
                    ->tooltip('Account balance after this line'),
            ])
            ->filters([
                Filter::make('transacted_on')
                    ->label('Date Range')
                    ->schema([
                        DatePicker::make('from')->label('From')->native(false),
                        DatePicker::make('until')->label('Until')->native(false),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query->when($data['from'], fn(
                        Builder $query,
                        $date,
                    ): Builder => $query->whereDate(
                        'ledger_entries.transacted_on',
                        '>=',
                        $date,
                    ))->when($data['until'], fn(Builder $query, $date): Builder => $query->whereDate(
                        'ledger_entries.transacted_on',
                        '<=',
                        $date,
                    )))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = 'From ' . $data['from'];
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = 'Until ' . $data['until'];
                        }

                        return $indicators;
                    }),

                SelectFilter::make('ledger_category_id')
                    ->label('Category')
                    ->options(fn(): array => LedgerCategory::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->placeholder('All categories'),

                SelectFilter::make('flow')
                    ->label('Direction')
                    ->options(PRFLedgerFlow::getOptions())
                    ->native(false)
                    ->placeholder('Receipts and payments'),
            ])
            ->paginated([15, 25, 50])
            ->striped()
            ->searchPlaceholder('Search counterparty or description...')
            ->emptyStateHeading('No cashbook lines yet')
            ->emptyStateDescription(
                'Receipt income, record payments or set the opening balance to start this cashbook.',
            )
            ->emptyStateIcon('heroicon-o-book-open');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return userCan(FinancialAccount::permission('view'));
    }
}
