<?php

namespace App\Filament\Resources\LedgerEntries;

use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFLedgerChannel;
use App\Enums\PRFLedgerFlow;
use App\Enums\PRFReceiptChannel;
use App\Enums\PRFResponsibleDesk;
use App\Filament\Resources\AccountTransfers\AccountTransferResource;
use App\Filament\Resources\LedgerEntries\Pages\ListLedgerEntries;
use App\Filament\Resources\LedgerEntries\Pages\ReceiptIncome;
use App\Filament\Resources\LedgerEntries\Pages\RecordPayment;
use App\Filament\Resources\LedgerEntries\Pages\ViewLedgerEntry;
use App\Filament\Resources\LedgerEntries\RelationManagers\ReceiptDeliveriesRelationManager;
use App\Jobs\LedgerEntry\UpdateJob;
use App\Jobs\ReceiptDelivery\CreateJob as CreateReceiptDeliveryJob;
use App\Models\FinancialAccount;
use App\Models\FinancialReport;
use App\Models\LedgerCategory;
use App\Models\LedgerEntry;
use App\Models\ReceiptDelivery;
use App\Rules\PhoneNumber;
use App\Services\Finance\ReceiptDocument;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;

/**
 * The treasurer's cashbook: every shilling in or out, receipted income and payments.
 */
class LedgerEntryResource extends Resource
{
    protected static ?string $model = LedgerEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Treasurer';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Cashbook';

    protected static ?string $modelLabel = 'Cashbook entry';

    protected static ?string $pluralModelLabel = 'Cashbook';

    protected static ?string $navigationTooltip = 'Receipt income, record payments and share receipts';

    /**
     * @return array<string, string>
     */
    public static function accountOptions(): array
    {
        return FinancialAccount::query()->active()->orderBy('name')->pluck('name', 'ulid')->all();
    }

    /**
     * @return array<string, string>
     */
    public static function incomeCategoryOptions(): array
    {
        return LedgerCategory::query()
            ->where('kind', PRFLedgerCategoryKind::INCOME->value)
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('name')
            ->pluck('name', 'ulid')
            ->all();
    }

    /**
     * EXPENSE and CHARGE categories grouped by desk, for the payment form.
     *
     * @return array<string, array<string, string>>
     */
    public static function paymentCategoryOptions(): array
    {
        return LedgerCategory::query()
            ->whereIn('kind', [PRFLedgerCategoryKind::EXPENSE->value, PRFLedgerCategoryKind::CHARGE->value])
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('name')
            ->get()
            ->groupBy(fn(LedgerCategory $category): string => $category->responsible_desk?->getLabel() ?? 'General')
            ->map(
                fn($group): array => $group->mapWithKeys(fn(LedgerCategory $category): array => [
                    $category->ulid => $category->name,
                ])->all(),
            )
            ->all();
    }

    /**
     * Categories a line can move to: only those of the same kind, so an income line stays income
     * (and keeps its receipt) and a payment stays a payment. The current category is always listed.
     *
     * @return array<string, string>
     */
    public static function editableCategoryOptions(?LedgerEntry $record = null): array
    {
        $current = $record?->ledgerCategory;

        return LedgerCategory::query()
            ->when(
                $current !== null,
                fn(Builder $query) => $query->where('kind', $current?->kind),
                fn(Builder $query) => $query->whereNot('kind', PRFLedgerCategoryKind::TRANSFER->value),
            )
            ->where(fn(Builder $query) => $query->where('is_active', true)->when($current
            !== null, fn(Builder $query) => $query->orWhere('id', $current?->id)))
            ->orderBy('sort')
            ->orderBy('name')
            ->pluck('name', 'ulid')
            ->all();
    }

    /**
     * Active accounts, plus the line's current account even if it has since been closed.
     *
     * @return array<string, string>
     */
    public static function editableAccountOptions(?LedgerEntry $record = null): array
    {
        return FinancialAccount::query()
            ->where(fn(Builder $query) => $query->where('is_active', true)->when($record
            !== null, fn(Builder $query) => $query->orWhere('id', $record?->financial_account_id)))
            ->orderBy('name')
            ->pluck('name', 'ulid')
            ->all();
    }

    /**
     * Lines the app posted from elsewhere (as opposed to keyed in by the treasurer).
     */
    public static function isAutoPosted(LedgerEntry $record): bool
    {
        return $record->source_key !== null && !str_starts_with($record->source_key, 'pledge_installment:');
    }

    public static function sourceLabel(?string $sourceKey): ?string
    {
        if ($sourceKey === null) {
            return null;
        }

        return match (true) {
            str_starts_with($sourceKey, 'payment:') => 'Paystack payment',
            str_starts_with($sourceKey, 'requisition:') => 'Requisition disbursement',
            str_starts_with($sourceKey, 'allocation_entry:') => 'Token of appreciation',
            str_starts_with($sourceKey, 'refund:') => 'Mission refund',
            str_starts_with($sourceKey, 'transfer:') => 'Account transfer',
            str_starts_with($sourceKey, 'import:') => 'Workbook import',
            str_starts_with($sourceKey, 'pledge_installment:') => 'Pledge installment',
            default => 'Auto-posted',
        };
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cashbook line')
                ->columnSpanFull()
                ->description('Correct what was recorded. Auto-posted lines are best fixed at their source.')
                ->icon('heroicon-o-book-open')
                ->schema([
                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            Select::make('financial_account_ulid')
                                ->label('Account')
                                ->options(fn(?LedgerEntry $record): array => static::editableAccountOptions($record))
                                ->required()
                                ->searchable()
                                ->preload()
                                ->native(false),

                            Select::make('ledger_category_ulid')
                                ->label('Category')
                                ->options(fn(?LedgerEntry $record): array => static::editableCategoryOptions($record))
                                ->helperText('Only categories of the same kind are offered, so income stays income.')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->native(false),

                            DatePicker::make('transacted_on')->label('Date')->required()->maxDate(now())->native(false),

                            Select::make('channel')
                                ->label('Channel')
                                ->options(PRFLedgerChannel::getOptions())
                                ->native(false),

                            TextInput::make('amount')
                                ->label('Amount')
                                ->required()
                                ->integer()
                                ->minValue(1)
                                ->prefix('KES'),

                            TextInput::make('reference')
                                ->label('Reference')
                                ->maxLength(255)
                                ->placeholder('M-Pesa code, bank slip or cheque no.'),

                            TextInput::make('counterparty')->label('Giver / Payee')->maxLength(255)->columnSpanFull(),

                            Textarea::make('description')->label('Description')->rows(2)->columnSpanFull(),
                        ]),
                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('giver_email')->label('Giver email')->email()->maxLength(255),

                            TextInput::make('giver_phone')
                                ->label('Giver phone')
                                ->maxLength(255)
                                ->rule(new PhoneNumber()),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transacted_on')->label('Date')->date('M j, Y')->sortable()->weight('medium'),

                TextColumn::make('receipt_number')
                    ->label('Receipt no.')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—')
                    ->weight('medium')
                    ->tooltip('Click to copy the receipt number'),

                TextColumn::make('financialAccount.name')->label('Account')->badge()->color('info')->sortable(),

                TextColumn::make('flow')
                    ->label('Flow')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state?->getLabel())
                    ->color(fn($state) => $state?->getColor())
                    ->sortable(),

                TextColumn::make('ledgerCategory.name')
                    ->label('Category')
                    ->description(
                        fn(LedgerEntry $record): ?string => $record->ledgerCategory?->responsible_desk?->getLabel(),
                    )
                    ->wrap(),

                TextColumn::make('channel')
                    ->label('Channel')
                    ->formatStateUsing(fn($state) => $state?->getLabel())
                    ->toggleable(),

                TextColumn::make('counterparty')
                    ->label('Giver / Payee')
                    ->searchable()
                    ->placeholder('—')
                    ->weight('medium'),

                TextColumn::make('amount')
                    ->label('Amount (KES)')
                    ->money('KES', divideBy: 1)
                    ->sortable()
                    ->weight('semibold')
                    ->color(fn(LedgerEntry $record): string => $record->flow === PRFLedgerFlow::RECEIPT
                        ? 'success'
                        : 'danger')
                    ->summarize([
                        Sum::make()
                            ->label('Total receipts')
                            ->money('KES', divideBy: 1)
                            ->query(fn(QueryBuilder $query) => $query->where('flow', PRFLedgerFlow::RECEIPT->value)),
                        Sum::make()
                            ->label('Total payments')
                            ->money('KES', divideBy: 1)
                            ->query(fn(QueryBuilder $query) => $query->where('flow', PRFLedgerFlow::PAYMENT->value)),
                    ]),

                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('source_key')
                    ->label('')
                    ->formatStateUsing(fn() => '')
                    ->icon(fn(LedgerEntry $record): ?string => static::isAutoPosted($record) ? 'heroicon-o-bolt' : null)
                    ->color('gray')
                    ->tooltip(fn(LedgerEntry $record): ?string => static::isAutoPosted($record)
                        ? 'Posted automatically from: ' . static::sourceLabel($record->source_key)
                        : null),

                TextColumn::make('recordedBy.name')
                    ->label('Recorded by')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('financial_account')
                    ->label('Account')
                    ->relationship('financialAccount', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('ledger_category')
                    ->label('Category')
                    ->relationship('ledgerCategory', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('kind')
                    ->label('Kind')
                    ->options(PRFLedgerCategoryKind::getOptions())
                    ->native(false)
                    ->query(fn(Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn(
                        Builder $query,
                        $kind,
                    ): Builder => $query->ofKind(PRFLedgerCategoryKind::from((int) $kind)))),

                SelectFilter::make('desk')
                    ->label('Desk')
                    ->options(PRFResponsibleDesk::getOptions())
                    ->native(false)
                    ->query(fn(Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn(
                        Builder $query,
                        $desk,
                    ): Builder => $query->whereHas('ledgerCategory', fn(Builder $categories): Builder => $categories->where(
                        'responsible_desk',
                        (int) $desk,
                    )))),

                SelectFilter::make('flow')->label('Flow')->options(PRFLedgerFlow::getOptions())->native(false),

                SelectFilter::make('channel')->label('Channel')->options(PRFLedgerChannel::getOptions())->native(false),

                Filter::make('transacted_on')
                    ->label('Date range')
                    ->schema([
                        DatePicker::make('from')->label('From')->default(now()->startOfMonth())->native(false),
                        DatePicker::make('to')->label('To')->default(now()->endOfMonth())->native(false),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query->when($data['from'] ?? null, fn(
                        Builder $query,
                        $date,
                    ): Builder => $query->whereDate('transacted_on', '>=', $date))->when($data['to'] ?? null, fn(
                        Builder $query,
                        $date,
                    ): Builder => $query->whereDate('transacted_on', '<=', $date))),

                Filter::make('has_receipt')
                    ->label('Has receipt')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('receipt_number')),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()->visible(fn(): bool => userCan(LedgerEntry::permission('view'))),

                Action::make('open_transfer')
                    ->label('Open transfer')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('gray')
                    ->tooltip(
                        'This line is one side of a transfer between accounts. Edit or delete the transfer instead.',
                    )
                    ->visible(
                        fn(LedgerEntry $record): bool => $record->account_transfer_id !== null && !$record->trashed(),
                    )
                    ->url(fn(LedgerEntry $record): ?string => (
                        $record->accountTransfer !== null
                            ? AccountTransferResource::getUrl('view', ['record' => $record->accountTransfer])
                            : null
                    )),

                EditAction::make()
                    ->visible(
                        fn(LedgerEntry $record): bool => (
                            userCan(LedgerEntry::permission('edit'))
                            && $record->account_transfer_id === null
                            && !$record->trashed()
                        ),
                    )
                    ->modalHeading('Edit cashbook line')
                    ->modalDescription(fn(LedgerEntry $record): string => static::isAutoPosted($record)
                        ? 'Warning: this line was auto-posted ('
                            . static::sourceLabel($record->source_key)
                            . '). Fixing it at the source is safer than editing it here.'
                        : 'Update the recorded details for this cashbook line.')
                    ->fillForm(fn(LedgerEntry $record): array => [
                        'financial_account_ulid' => $record->financialAccount?->ulid,
                        'ledger_category_ulid' => $record->ledgerCategory?->ulid,
                        'transacted_on' => $record->transacted_on?->format('Y-m-d'),
                        'channel' => $record->channel?->value,
                        'amount' => $record->amount,
                        'reference' => $record->reference,
                        'counterparty' => $record->counterparty,
                        'description' => $record->description,
                        'giver_email' => $record->giver_email,
                        'giver_phone' => $record->giver_phone,
                    ])
                    ->using(fn(LedgerEntry $record, array $data): LedgerEntry => UpdateJob::dispatchSync(
                        $data,
                        $record->ulid,
                    ))
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Cashbook line updated')
                            ->body('The changes have been saved.'),
                    ),

                ActionGroup::make([
                    Action::make('print_pdf')
                        ->label('Print / Download PDF')
                        ->icon('heroicon-o-printer')
                        ->action(fn(LedgerEntry $record) => response()->streamDownload(function () use ($record): void {
                            print app(ReceiptDocument::class)->pdf($record);
                        }, app(ReceiptDocument::class)->filename($record))),

                    Action::make('email_receipt')
                        ->label('Email receipt')
                        ->icon('heroicon-o-envelope')
                        ->schema([
                            TextInput::make('email')->label('Email address')->email()->required()->maxLength(255),
                        ])
                        ->fillForm(fn(LedgerEntry $record): array => ['email' => $record->giver_email])
                        ->action(function (LedgerEntry $record, array $data): void {
                            static::sendReceipt($record, PRFReceiptChannel::EMAIL, $data['email']);
                        }),

                    Action::make('sms_receipt')
                        ->label('SMS receipt')
                        ->icon('heroicon-o-chat-bubble-left')
                        ->schema([
                            TextInput::make('phone')
                                ->label('Phone number')
                                ->required()
                                ->maxLength(255)
                                ->rule(new PhoneNumber()),
                        ])
                        ->fillForm(fn(LedgerEntry $record): array => ['phone' => $record->giver_phone])
                        ->action(function (LedgerEntry $record, array $data): void {
                            static::sendReceipt($record, PRFReceiptChannel::SMS, $data['phone']);
                        }),

                    Action::make('whatsapp_receipt')
                        ->label('Share on WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->schema([
                            TextInput::make('phone')
                                ->label('Phone number')
                                ->required()
                                ->maxLength(255)
                                ->rule(new PhoneNumber()),
                        ])
                        ->fillForm(fn(LedgerEntry $record): array => ['phone' => $record->giver_phone])
                        ->action(function (LedgerEntry $record, array $data): void {
                            try {
                                $delivery = CreateReceiptDeliveryJob::dispatchSync([
                                    'ledger_entry_ulid' => $record->ulid,
                                    'channel' => PRFReceiptChannel::WHATSAPP->value,
                                    'recipient' => $data['phone'],
                                    'requested_by' => auth()->id(),
                                ]);
                            } catch (InvalidArgumentException $exception) {
                                Notification::make()
                                    ->danger()
                                    ->title('Receipt not shared')
                                    ->body($exception->getMessage())
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->success()
                                ->title('WhatsApp share link ready')
                                ->body('Open WhatsApp to send the receipt from your phone.')
                                ->actions([
                                    Action::make('open_whatsapp')
                                        ->label('Open WhatsApp')
                                        ->icon('heroicon-o-chat-bubble-left-right')
                                        ->url($delivery->share_url, shouldOpenInNewTab: true),
                                ])
                                ->send();
                        }),

                    Action::make('copy_link')
                        ->label('Open receipt link')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn(LedgerEntry $record): string => app(ReceiptDocument::class)->url(
                            $record,
                        ), shouldOpenInNewTab: true),
                ])
                    ->label('Receipt')
                    ->icon('heroicon-o-ticket')
                    ->button()
                    ->color('success')
                    ->size('sm')
                    ->visible(
                        fn(LedgerEntry $record): bool => (
                            $record->receipt_number !== null
                            && $record->ledgerCategory?->kind === PRFLedgerCategoryKind::INCOME
                            && !$record->trashed()
                            && userCan(ReceiptDelivery::permission('create'))
                        ),
                    ),

                DeleteAction::make()
                    ->visible(
                        fn(LedgerEntry $record): bool => (
                            userCan(LedgerEntry::permission('delete'))
                            && $record->account_transfer_id === null
                        ),
                    )
                    ->modalDescription(fn(LedgerEntry $record): string => static::isAutoPosted($record)
                        ? 'This line was posted automatically ('
                            . static::sourceLabel($record->source_key)
                            . '). Deleting it removes it from the balances; it will not be posted again. You can restore it from the Deleted filter.'
                        : 'The line is removed from the balances. You can restore it from the Deleted filter.'),

                RestoreAction::make()->visible(fn(): bool => userCan(LedgerEntry::permission('delete'))),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn(): bool => userCan(LedgerEntry::permission('delete'))),
                    RestoreBulkAction::make()->visible(fn(): bool => userCan(LedgerEntry::permission('delete'))),
                ]),
            ])
            ->defaultSort(fn(Builder $query): Builder => $query->orderByDesc('transacted_on')->orderByDesc('id'))
            ->striped()
            ->searchPlaceholder('Search giver, reference or receipt no.')
            ->emptyStateIcon('heroicon-o-book-open')
            ->emptyStateHeading('No cashbook entries in this view')
            ->emptyStateDescription(
                'Receipt income when money comes in, record payments when it goes out. Try widening the date range filter if you expected to see entries here.',
            );
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cashbook line')
                ->columnSpanFull()
                ->icon('heroicon-o-book-open')
                ->schema([
                    Grid::make(3)
                        ->columnSpanFull()
                        ->schema([
                            TextEntry::make('transacted_on')->label('Date')->date('M j, Y'),
                            TextEntry::make('financialAccount.name')->label('Account')->badge()->color('info'),
                            TextEntry::make('flow')
                                ->label('Flow')
                                ->badge()
                                ->formatStateUsing(fn($state) => $state?->getLabel())
                                ->color(fn($state) => $state?->getColor()),
                            TextEntry::make('ledgerCategory.name')->label('Category'),
                            TextEntry::make('ledgerCategory.responsible_desk')
                                ->label('Desk')
                                ->formatStateUsing(fn($state) => $state?->getLabel())
                                ->placeholder('—'),
                            TextEntry::make('channel')
                                ->label('Channel')
                                ->formatStateUsing(fn($state) => $state?->getLabel())
                                ->placeholder('—'),
                            TextEntry::make('amount')->label('Amount')->money('KES', divideBy: 1)->weight('bold'),
                            TextEntry::make('counterparty')->label('Giver / Payee')->placeholder('—'),
                            TextEntry::make('reference')->label('Reference')->placeholder('—')->copyable(),
                            TextEntry::make('description')->label('Description')->placeholder('—')->columnSpanFull(),
                        ]),
                ]),

            Section::make('Receipt')
                ->columnSpanFull()
                ->icon('heroicon-o-ticket')
                ->visible(fn(LedgerEntry $record): bool => $record->receipt_number !== null)
                ->schema([
                    Grid::make(3)
                        ->columnSpanFull()
                        ->schema([
                            TextEntry::make('receipt_number')->label('Receipt number')->copyable()->weight('bold'),
                            TextEntry::make('receipt_link')
                                ->label('Receipt link')
                                ->state(fn(LedgerEntry $record): string => app(ReceiptDocument::class)->url($record))
                                ->url(fn(LedgerEntry $record): string => app(ReceiptDocument::class)->url(
                                    $record,
                                ), shouldOpenInNewTab: true)
                                ->copyable()
                                ->limit(40),
                            TextEntry::make('whatsapp_link')
                                ->label('WhatsApp')
                                ->state(
                                    fn(LedgerEntry $record): string => app(ReceiptDocument::class)->whatsAppURL(
                                        $record,
                                    ),
                                )
                                ->url(fn(LedgerEntry $record): string => app(ReceiptDocument::class)->whatsAppURL(
                                    $record,
                                ), shouldOpenInNewTab: true)
                                ->limit(40),
                            TextEntry::make('giver_email')->label('Giver email')->placeholder('—')->copyable(),
                            TextEntry::make('giver_phone')->label('Giver phone')->placeholder('—')->copyable(),
                            TextEntry::make('member.full_name')->label('Member')->placeholder('—'),
                        ]),
                ]),

            Section::make('Source')
                ->columnSpanFull()
                ->icon('heroicon-o-link')
                ->schema([
                    Grid::make(3)
                        ->columnSpanFull()
                        ->schema([
                            TextEntry::make('source_key')
                                ->label('Posted from')
                                ->formatStateUsing(fn(?string $state): string => $state === null
                                    ? 'Keyed in by the treasurer'
                                    : 'Auto-posted: ' . static::sourceLabel($state))
                                ->columnSpanFull(),
                            TextEntry::make('payment.ulid')->label('Payment')->placeholder('—')->copyable(),
                            TextEntry::make('requisition.ulid')->label('Requisition')->placeholder('—')->copyable(),
                            TextEntry::make('refund.ulid')->label('Refund')->placeholder('—')->copyable(),
                            TextEntry::make('accountTransfer.ulid')->label('Transfer')->placeholder('—')->copyable(),
                            TextEntry::make('accountingEvent.name')->label('Accounting event')->placeholder('—'),
                            TextEntry::make('pledgeInstallment.ulid')
                                ->label('Pledge installment')
                                ->placeholder('—')
                                ->copyable(),
                        ]),
                ]),

            Section::make('Recorded')
                ->columnSpanFull()
                ->icon('heroicon-o-user')
                ->schema([
                    Grid::make(3)
                        ->columnSpanFull()
                        ->schema([
                            TextEntry::make('recordedBy.name')->label('Recorded by')->placeholder('—'),
                            TextEntry::make('created_at')->label('Recorded on')->dateTime('M j, Y g:i A'),
                            TextEntry::make('updated_at')->label('Last updated')->dateTime('M j, Y g:i A'),
                        ]),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            ReceiptDeliveriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLedgerEntries::route('/'),
            'receipt' => ReceiptIncome::route('/receipt'),
            'pay' => RecordPayment::route('/pay'),
            'view' => ViewLedgerEntry::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['financialAccount', 'ledgerCategory', 'recordedBy', 'accountTransfer'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canAccess(): bool
    {
        return userCan(LedgerEntry::permission('viewAny'));
    }

    protected static function sendReceipt(LedgerEntry $record, PRFReceiptChannel $channel, ?string $recipient): void
    {
        try {
            CreateReceiptDeliveryJob::dispatchSync([
                'ledger_entry_ulid' => $record->ulid,
                'channel' => $channel->value,
                'recipient' => $recipient,
                'requested_by' => auth()->id(),
            ]);
        } catch (InvalidArgumentException $exception) {
            Notification::make()->danger()->title('Receipt not sent')->body($exception->getMessage())->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('Receipt sending')
            ->body("The receipt is on its way by {$channel->getLabel()}.")
            ->send();
    }

    public static function canCreateReceipt(): bool
    {
        return userCan(LedgerEntry::permission('create'));
    }

    public static function canGenerateReport(): bool
    {
        return userCan(FinancialReport::permission('create'));
    }
}
