<?php

namespace App\Filament\Resources\TransferRates;

use App\Enums\PRFTransactionType;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\TransferRates\Pages\CreateTransferRate;
use App\Filament\Resources\TransferRates\Pages\EditTransferRate;
use App\Filament\Resources\TransferRates\Pages\ListTransferRates;
use App\Filament\Resources\TransferRates\Pages\ViewTransferRate;
use App\Models\TransferRate;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class TransferRateResource extends Resource
{
    protected static ?string $model = TransferRate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $modelLabel = 'Transfer Rate';

    protected static ?string $pluralModelLabel = 'Transfer Rates';

    protected static ?string $navigationLabel = 'Transfer Rates';

    protected static ?string $navigationTooltip = 'Manage transaction fee rates and charges';

    protected static ?string $recordTitleAttribute = 'transaction_type';

    protected static int $globalSearchResultsLimit = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transfer Rate Configuration')
                    ->description('Configure transaction fees and service charges for different amount ranges')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        StatusSchema::enumSelect(
                            name: 'transaction_type',
                            label: 'Transaction Type',
                            enumClass: PRFTransactionType::class,
                            hiddenOnCreate: false,
                            helperText: 'The type of transaction this rate applies to',
                        )->columnSpanFull(),

                        Grid::make()
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('min_amount')
                                    ->label('Minimum Amount (KSh)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('KSh')
                                    ->placeholder('e.g., 100')
                                    ->helperText('The lowest transaction amount this rate applies to'),

                                TextInput::make('max_amount')
                                    ->label('Maximum Amount (KSh)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('KSh')
                                    ->placeholder('e.g., 5000')
                                    ->helperText('The highest transaction amount this rate applies to'),

                                TextInput::make('charge')
                                    ->label('Service Charge (KSh)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('KSh')
                                    ->placeholder('e.g., 50')
                                    ->helperText('The fee charged for transactions in this range'),
                            ])->columns(3),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('transaction_type')
                    ->label('Transaction Type')
                    ->formatStateUsing(fn (string $state): string => PRFTransactionType::fromValue($state)->getLabel())
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-credit-card')
                    ->searchable()
                    ->tooltip('Type of transaction'),

                TextColumn::make('min_amount')
                    ->label('Min Amount')
                    ->numeric()
                    ->sortable()
                    ->money('KES')
                    ->icon('heroicon-o-arrow-up')
                    ->tooltip('Minimum transaction amount'),

                TextColumn::make('max_amount')
                    ->label('Max Amount')
                    ->numeric()
                    ->sortable()
                    ->money('KES')
                    ->icon('heroicon-o-arrow-down')
                    ->tooltip('Maximum transaction amount'),

                TextColumn::make('charge')
                    ->label('Service Charge')
                    ->numeric()
                    ->sortable()
                    ->money('KES')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-banknotes')
                    ->tooltip('Fee charged for this range'),

                TextColumn::make('amount_range')
                    ->label('Amount Range')
                    ->getStateUsing(fn ($record) => 'KSh '.number_format($record->min_amount).' - KSh '.number_format($record->max_amount))
                    ->icon('heroicon-o-arrows-right-left')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Full amount range'),

                TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->tooltip(fn ($record) => 'Created: '.$record->created_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date rate was deleted'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                SelectFilter::make('transaction_type')
                    ->label('Transaction Type')
                    ->options(PRFTransactionType::getOptions())
                    ->placeholder('All Types'),

                Filter::make('amount_range')
                    ->schema([
                        TextInput::make('min_charge')
                            ->label('Minimum Charge')
                            ->numeric()
                            ->placeholder('e.g., 10'),
                        TextInput::make('max_charge')
                            ->label('Maximum Charge')
                            ->numeric()
                            ->placeholder('e.g., 100'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_charge'],
                                fn (Builder $query, $charge): Builder => $query->where('charge', '>=', $charge),
                            )
                            ->when(
                                $data['max_charge'],
                                fn (Builder $query, $charge): Builder => $query->where('charge', '<=', $charge),
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view transfer rate')),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit transfer rate')),

                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete transfer rate')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete transfer rate')),
                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete transfer rate')),
                    BulkAction::make('update_charges')
                        ->label('Update Charges')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->form([
                            TextInput::make('new_charge')
                                ->label('New Charge Amount')
                                ->required()
                                ->numeric()
                                ->prefix('KSh')
                                ->placeholder('e.g., 50')
                                ->helperText('This amount will be applied to all selected rates'),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update(['charge' => $data['new_charge']]);
                            }
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit transfer rate')),
                ])->visible(fn () => userCan('delete transfer rate')),
            ])
            ->defaultSort('min_amount', 'asc')
            ->searchPlaceholder('Search transfer rates...')
            ->emptyStateHeading('No transfer rates found')
            ->emptyStateDescription('Start by adding your first transfer rate to the system.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransferRates::route('/'),
            'create' => CreateTransferRate::route('/create'),
            'view' => ViewTransferRate::route('/{record}'),
            'edit' => EditTransferRate::route('/{record}/edit'),
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
        return userCan('viewAny transfer rate');
    }
}
