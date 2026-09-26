<?php

namespace App\Filament\Resources\FinancialAccounts;

use App\Enums\PRFFinancialAccountType;
use App\Enums\PRFLedgerFlow;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Resources\FinancialAccounts\Pages\CreateFinancialAccount;
use App\Filament\Resources\FinancialAccounts\Pages\EditFinancialAccount;
use App\Filament\Resources\FinancialAccounts\Pages\ListFinancialAccounts;
use App\Filament\Resources\FinancialAccounts\Pages\ViewFinancialAccount;
use App\Filament\Resources\FinancialAccounts\RelationManagers\LedgerEntriesRelationManager;
use App\Jobs\LedgerEntry\CreateJob as CreateLedgerEntryJob;
use App\Models\FinancialAccount;
use App\Models\LedgerEntry;
use App\Services\Finance\ChartOfAccounts;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Throwable;

class FinancialAccountResource extends Resource
{
    protected static ?string $model = FinancialAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static string|\UnitEnum|null $navigationGroup = 'Treasurer';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Financial Account';

    protected static ?string $pluralModelLabel = 'Financial Accounts';

    protected static ?string $navigationLabel = 'Accounts';

    protected static ?string $navigationTooltip = 'Where the fellowship holds money: one cashbook per account';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account Details')
                ->columnSpanFull()
                ->description('Add a place where the fellowship holds money (Paybill, M-Pesa, bank, cash…)')
                ->icon('heroicon-o-wallet')
                ->schema([
                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            ContentSchema::nameField(
                                name: 'name',
                                label: 'Account Name',
                                placeholder: 'e.g., Paybill, M-Pesa, Bank',
                                helperText: 'A unique name for this account within your fellowship',
                            )
                                ->unique(ignoreRecord: true)
                                ->prefixIcon('heroicon-o-wallet'),

                            Select::make('type')
                                ->label('Account Type')
                                ->options(PRFFinancialAccountType::getOptions())
                                ->required()
                                ->native(false)
                                ->prefixIcon('heroicon-o-building-library')
                                ->helperText('Determines the default channel for cashbook lines posted here'),
                        ]),

                    TextInput::make('identifier')
                        ->label('Identifier')
                        ->maxLength(255)
                        ->placeholder('e.g., paybill number, till number, account number')
                        ->helperText('Paybill/till/account number. Never store PINs or passwords here.')
                        ->prefixIcon('heroicon-o-identification')
                        ->columnSpanFull(),

                    ContentSchema::descriptionField(
                        name: 'description',
                        label: 'Description',
                        placeholder: 'Anything the next treasurer should know about this account…',
                        helperText: 'Optional notes about this account',
                    ),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive accounts are hidden when receipting, paying or transferring'),
                ])
                ->collapsible()
                ->persistCollapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query): Builder => $query->withBalance()->withMax('ledgerEntries', 'transacted_on'),
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Account')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-wallet')
                    ->tooltip('Where the fellowship holds this money'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn(FinancialAccount $record): string => $record->type?->getLabel() ?? '—')
                    ->color(fn(FinancialAccount $record): string => $record->type?->getColor() ?? 'gray')
                    ->icon(fn(FinancialAccount $record): ?string => $record->type?->getIcon())
                    ->sortable()
                    ->tooltip('The kind of account this is'),

                TextColumn::make('identifier')
                    ->label('Identifier')
                    ->searchable()
                    ->copyable()
                    ->toggleable()
                    ->placeholder('—')
                    ->tooltip('Paybill/till/account number'),

                TextColumn::make('balance')
                    ->label('Balance (KES)')
                    ->state(fn(FinancialAccount $record): int => $record->balance)
                    ->money('KES', divideBy: 1)
                    ->weight('semibold')
                    ->color(fn(FinancialAccount $record): string => $record->balance < 0 ? 'danger' : 'success')
                    ->tooltip('Receipts minus payments across the whole cashbook'),

                TextColumn::make('ledger_entries_max_transacted_on')
                    ->label('Last Entry')
                    ->date('M j, Y')
                    ->sortable()
                    ->color('gray')
                    ->placeholder('No entries yet')
                    ->tooltip('Date of the most recent cashbook line'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-pause-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn(FinancialAccount $record): string => $record->is_active
                        ? 'Active: available when receipting, paying or transferring'
                        : 'Inactive: hidden from money forms'),
            ])
            ->filters([
                TrashedFilter::make()->native(false)->label('Show Deleted')->placeholder('Active accounts only'),

                SelectFilter::make('type')
                    ->label('Filter by Type')
                    ->options(PRFFinancialAccountType::getOptions())
                    ->native(false)
                    ->placeholder('All types'),

                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All accounts')
                    ->trueLabel('Active accounts')
                    ->falseLabel('Inactive accounts'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn() => userCan(FinancialAccount::permission('view')))
                    ->tooltip('Open the account cashbook'),

                EditAction::make()
                    ->visible(fn() => userCan(FinancialAccount::permission('edit')))
                    ->tooltip('Make changes to this account'),

                static::setOpeningBalanceAction(),

                DeleteAction::make()->visible(fn() => userCan(FinancialAccount::permission('delete'))),
                RestoreAction::make()->visible(fn() => userCan(FinancialAccount::permission('restore'))),
                ForceDeleteAction::make()->visible(
                    fn(FinancialAccount $record) => (
                        userCan(FinancialAccount::permission('forceDelete'))
                        && !$record->ledgerEntries()->withTrashed()->exists()
                    ),
                ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn() => userCan(FinancialAccount::permission('delete'))),
                    RestoreBulkAction::make()->visible(fn() => userCan(FinancialAccount::permission('delete'))),
                ]),
            ])
            ->defaultSort('name')
            ->striped()
            ->searchPlaceholder('Search accounts...')
            ->emptyStateHeading('No financial accounts found')
            ->emptyStateDescription(
                'Seeded accounts appear here automatically; add extras like a money market account as needed.',
            )
            ->emptyStateIcon('heroicon-o-wallet');
    }

    public static function infolist(Schema $schema): Schema
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        return $schema->components([
            Section::make('Account')
                ->columnSpanFull()
                ->icon('heroicon-o-wallet')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('name')->label('Account')->weight('bold')->icon('heroicon-o-wallet'),
                        TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->formatStateUsing(
                                fn(FinancialAccount $record): string => $record->type?->getLabel() ?? '—',
                            )
                            ->color(fn(FinancialAccount $record): string => $record->type?->getColor() ?? 'gray'),
                        TextEntry::make('identifier')->label('Identifier')->placeholder('—')->copyable(),
                        TextEntry::make('description')->label('Description')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn(FinancialAccount $record): string => $record->is_active
                                ? 'Active'
                                : 'Inactive')
                            ->color(fn(FinancialAccount $record): string => $record->is_active ? 'success' : 'warning'),
                    ]),
                ]),

            Section::make('Balances')
                ->columnSpanFull()
                ->description('Receipts minus payments; this month counts posted lines only')
                ->icon('heroicon-o-banknotes')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('balance')
                            ->label('Current Balance')
                            ->money('KES', divideBy: 1)
                            ->weight('bold')
                            ->state(fn(FinancialAccount $record): int => $record->balance)
                            ->color(fn(FinancialAccount $record): string => $record->balance < 0
                                ? 'danger'
                                : 'success'),

                        TextEntry::make('receipts_this_month')
                            ->label('Receipts This Month')
                            ->money('KES', divideBy: 1)
                            ->color('success')
                            ->state(
                                fn(FinancialAccount $record): int => (int) $record
                                    ->ledgerEntries()
                                    ->where('flow', PRFLedgerFlow::RECEIPT)
                                    ->whereBetween('transacted_on', [$monthStart, $monthEnd])
                                    ->sum('amount'),
                            ),

                        TextEntry::make('payments_this_month')
                            ->label('Payments This Month')
                            ->money('KES', divideBy: 1)
                            ->color('danger')
                            ->state(
                                fn(FinancialAccount $record): int => (int) $record
                                    ->ledgerEntries()
                                    ->where('flow', PRFLedgerFlow::PAYMENT)
                                    ->whereBetween('transacted_on', [$monthStart, $monthEnd])
                                    ->sum('amount'),
                            ),
                    ]),
                ]),
        ]);
    }

    /**
     * Header and row action that posts the account's starting point to the cashbook.
     */
    public static function setOpeningBalanceAction(): Action
    {
        return Action::make('set_opening_balance')
            ->label('Set opening balance')
            ->icon('heroicon-o-banknotes')
            ->color('warning')
            ->schema([
                Select::make('financial_account_ulid')
                    ->label('Account')
                    ->options(fn(): array => FinancialAccount::query()->orderBy('name')->pluck('name', 'ulid')->all())
                    ->default(fn(?FinancialAccount $record): ?string => $record?->ulid)
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->helperText('The account this opening balance belongs to'),

                DatePicker::make('transacted_on')
                    ->label('As-at date')
                    ->default(now()->toDateString())
                    ->required()
                    ->native(false)
                    ->helperText('Usually 1 January of the year being opened'),

                TextInput::make('amount')
                    ->label('Balance on that date')
                    ->integer()
                    ->required()
                    ->notIn(['0'])
                    ->prefix('KES')
                    ->helperText('What the account held on that date. Use a minus sign only if it was overdrawn.'),

                TextInput::make('description')->label('Description')->default('Opening balance')->maxLength(255),
            ])
            ->action(function (array $data, ?FinancialAccount $record = null): void {
                $amount = (int) $data['amount'];

                try {
                    $category = app(ChartOfAccounts::class)->openingBalance();

                    CreateLedgerEntryJob::dispatchSync([
                        'financial_account_ulid' => $data['financial_account_ulid'] ?? $record?->ulid,
                        'ledger_category_ulid' => $category->ulid,
                        'amount' => abs($amount),
                        'flow' => ($amount < 0 ? PRFLedgerFlow::PAYMENT : PRFLedgerFlow::RECEIPT)->value,
                        'transacted_on' => $data['transacted_on'],
                        'description' => $data['description'] ?? 'Opening balance',
                        'recorded_by' => Auth::id(),
                    ]);
                } catch (Throwable $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Opening balance not recorded')
                        ->body($exception->getMessage())
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Opening balance recorded')
                    ->body('The account balance has been updated.')
                    ->send();
            })
            ->visible(fn(): bool => userCan(LedgerEntry::permission('create')))
            ->tooltip('Post the account starting balance to the cashbook');
    }

    public static function getRelations(): array
    {
        return [
            LedgerEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinancialAccounts::route('/'),
            'create' => CreateFinancialAccount::route('/create'),
            'view' => ViewFinancialAccount::route('/{record}'),
            'edit' => EditFinancialAccount::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canAccess(): bool
    {
        return userCan(FinancialAccount::permission('viewAny'));
    }
}
