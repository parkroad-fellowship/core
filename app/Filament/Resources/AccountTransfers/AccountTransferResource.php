<?php

namespace App\Filament\Resources\AccountTransfers;

use App\Enums\PRFTransactionType;
use App\Filament\Resources\AccountTransfers\Pages\CreateAccountTransfer;
use App\Filament\Resources\AccountTransfers\Pages\EditAccountTransfer;
use App\Filament\Resources\AccountTransfers\Pages\ListAccountTransfers;
use App\Filament\Resources\AccountTransfers\Pages\ViewAccountTransfer;
use App\Helpers\Utils;
use App\Models\AccountTransfer;
use App\Models\FinancialAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountTransferResource extends Resource
{
    protected static ?string $model = AccountTransfer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Treasurer';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Account Transfer';

    protected static ?string $pluralModelLabel = 'Account Transfers';

    protected static ?string $navigationLabel = 'Transfers';

    protected static ?string $navigationTooltip = 'Money moved between the fellowship’s own accounts';

    /**
     * @return array<string, string>
     */
    public static function accountOptions(): array
    {
        return FinancialAccount::query()->active()->orderBy('name')->pluck('name', 'ulid')->all();
    }

    /**
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    public static function transferSchemaFields(): array
    {
        return [
            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('from_financial_account_ulid')
                        ->label('From Account')
                        ->options(fn(): array => static::accountOptions())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->prefixIcon('heroicon-o-arrow-up-tray')
                        ->helperText('The account the money leaves'),

                    Select::make('to_financial_account_ulid')
                        ->label('To Account')
                        ->options(fn(): array => static::accountOptions())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->prefixIcon('heroicon-o-arrow-down-tray')
                        ->different('from_financial_account_ulid')
                        ->helperText('The account the money arrives in (must differ)'),
                ]),

            Grid::make(3)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('amount')
                        ->label('Amount')
                        ->integer()
                        ->required()
                        ->minValue(1)
                        ->prefix('KES')
                        ->live(onBlur: true)
                        ->placeholder('e.g. 50000')
                        ->helperText('How much moves between the accounts'),

                    TextInput::make('charge')
                        ->label('Charge')
                        ->integer()
                        ->required()
                        ->default(0)
                        ->minValue(0)
                        ->prefix('KES')
                        ->helperText(
                            fn(Get $get): string => (
                                'M-Pesa charge hint: KES '
                                . number_format(Utils::getCharge(
                                    PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF,
                                    (int) ($get('amount') ?? 0),
                                ))
                                . '. The charge is booked as a Treasurer’s Desk expense.'
                            ),
                        ),

                    DatePicker::make('transferred_on')
                        ->label('Transfer Date')
                        ->default(now()->toDateString())
                        ->required()
                        ->maxDate(now())
                        ->native(false)
                        ->helperText('When the money moved'),
                ]),

            TextInput::make('reference')
                ->label('Reference')
                ->maxLength(255)
                ->placeholder('e.g., M-Pesa code or bank slip no.')
                ->columnSpanFull(),

            TextInput::make('description')
                ->label('Description')
                ->maxLength(1000)
                ->placeholder('Why was this money moved?')
                ->columnSpanFull(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Transfer Details')
                ->columnSpanFull()
                ->description(
                    'Move money between two of the fellowship’s accounts. Neither side is income or expense; only the charge is an expense.',
                )
                ->icon('heroicon-o-arrows-right-left')
                ->schema(static::transferSchemaFields())
                ->collapsible()
                ->persistCollapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transferred_on')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->tooltip('When the money moved'),

                TextColumn::make('fromAccount.name')
                    ->label('From')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->sortable()
                    ->tooltip('The account the money left'),

                TextColumn::make('toAccount.name')
                    ->label('To')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->sortable()
                    ->tooltip('The account the money arrived in'),

                TextColumn::make('amount')
                    ->label('Amount (KES)')
                    ->money('KES', divideBy: 1)
                    ->sortable()
                    ->weight('semibold')
                    ->tooltip('How much moved'),

                TextColumn::make('charge')
                    ->label('Charge (KES)')
                    ->money('KES', divideBy: 1)
                    ->sortable()
                    ->toggleable()
                    ->color('gray')
                    ->tooltip('Transfer charge, booked as a Treasurer’s Desk expense'),

                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable()
                    ->toggleable()
                    ->placeholder('—')
                    ->tooltip('M-Pesa code or bank slip no.'),
            ])
            ->filters([
                TrashedFilter::make()->native(false)->label('Show Deleted')->placeholder('Active transfers only'),

                SelectFilter::make('from_financial_account_id')
                    ->label('From Account')
                    ->relationship('fromAccount', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->placeholder('All accounts'),

                SelectFilter::make('to_financial_account_id')
                    ->label('To Account')
                    ->relationship('toAccount', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->placeholder('All accounts'),

                Filter::make('transferred_on')
                    ->label('Transfer Date')
                    ->schema([
                        DatePicker::make('from')->label('From')->native(false),
                        DatePicker::make('until')->label('Until')->native(false),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query->when($data['from'], fn(
                        Builder $query,
                        $date,
                    ): Builder => $query->whereDate('transferred_on', '>=', $date))->when($data['until'], fn(
                        Builder $query,
                        $date,
                    ): Builder => $query->whereDate('transferred_on', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn(): bool => userCan(AccountTransfer::permission('view')))
                    ->tooltip('View this transfer'),

                EditAction::make()
                    ->visible(fn(): bool => userCan(AccountTransfer::permission('edit')))
                    ->tooltip('Correct this transfer (its cashbook lines are re-posted)'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn(): bool => userCan(AccountTransfer::permission('delete'))),
                    ForceDeleteBulkAction::make()->visible(fn(): bool => userCan(AccountTransfer::permission(
                        'delete',
                    ))),
                    RestoreBulkAction::make()->visible(fn(): bool => userCan(AccountTransfer::permission('delete'))),
                ]),
            ])
            ->defaultSort('transferred_on', 'desc')
            ->striped()
            ->searchPlaceholder('Search by reference...')
            ->emptyStateHeading('No transfers found')
            ->emptyStateDescription(
                'Record a transfer when money moves between the fellowship’s own accounts, e.g. Paybill to Bank.',
            )
            ->emptyStateIcon('heroicon-o-arrows-right-left');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Transfer')
                ->columnSpanFull()
                ->icon('heroicon-o-arrows-right-left')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('fromAccount.name')
                            ->label('From Account')
                            ->badge()
                            ->color('warning')
                            ->icon('heroicon-o-arrow-up-tray'),

                        TextEntry::make('toAccount.name')
                            ->label('To Account')
                            ->badge()
                            ->color('success')
                            ->icon('heroicon-o-arrow-down-tray'),

                        TextEntry::make('transferred_on')->label('Transfer Date')->date('M j, Y'),

                        TextEntry::make('amount')->label('Amount')->money('KES', divideBy: 1)->weight('bold'),

                        TextEntry::make('charge')->label('Charge')->money('KES', divideBy: 1)->color('gray'),

                        TextEntry::make('reference')->label('Reference')->placeholder('—')->copyable(),

                        TextEntry::make('description')->label('Description')->placeholder('—')->columnSpanFull(),
                    ]),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccountTransfers::route('/'),
            'create' => CreateAccountTransfer::route('/create'),
            'view' => ViewAccountTransfer::route('/{record}'),
            'edit' => EditAccountTransfer::route('/{record}/edit'),
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
        return userCan(AccountTransfer::permission('viewAny'));
    }
}
