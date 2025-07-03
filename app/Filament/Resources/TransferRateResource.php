<?php

namespace App\Filament\Resources;

use App\Enums\PRFTransactionType;
use App\Filament\Resources\TransferRateResource\Pages;
use App\Models\TransferRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class TransferRateResource extends Resource
{
    protected static ?string $model = TransferRate::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $modelLabel = 'Transfer Rate';

    protected static ?string $pluralModelLabel = 'Transfer Rates';

    protected static ?string $navigationLabel = 'Transfer Rates';

    protected static ?string $navigationTooltip = 'Manage transaction fee rates and charges';

    protected static ?string $recordTitleAttribute = 'transaction_type';

    protected static int $globalSearchResultsLimit = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transfer Rate Configuration')
                    ->description('Configure transaction fees and charges')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\Select::make('transaction_type')
                            ->label('Transaction Type')
                            ->required()
                            ->options(PRFTransactionType::getOptions())
                            ->helperText('💳 Select the type of transaction')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('min_amount')
                                    ->label('Minimum Amount (KSh)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('KSh')
                                    ->helperText('💰 Minimum transaction amount'),

                                Forms\Components\TextInput::make('max_amount')
                                    ->label('Maximum Amount (KSh)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('KSh')
                                    ->helperText('💰 Maximum transaction amount'),

                                Forms\Components\TextInput::make('charge')
                                    ->label('Service Charge (KSh)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('KSh')
                                    ->helperText('💵 Fee charged for this transaction range'),
                            ])->columns(3),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->columns([
                Tables\Columns\TextColumn::make('transaction_type')
                    ->label('Transaction Type')
                    ->formatStateUsing(fn (string $state): string => PRFTransactionType::fromValue($state)->getLabel())
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-credit-card')
                    ->searchable(),

                Tables\Columns\TextColumn::make('min_amount')
                    ->label('Min Amount')
                    ->numeric()
                    ->sortable()
                    ->money('KES')
                    ->icon('heroicon-o-arrow-up'),

                Tables\Columns\TextColumn::make('max_amount')
                    ->label('Max Amount')
                    ->numeric()
                    ->sortable()
                    ->money('KES')
                    ->icon('heroicon-o-arrow-down'),

                Tables\Columns\TextColumn::make('charge')
                    ->label('Service Charge')
                    ->numeric()
                    ->sortable()
                    ->money('KES')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-banknotes'),

                Tables\Columns\TextColumn::make('amount_range')
                    ->label('Amount Range')
                    ->getStateUsing(fn ($record) => 'KSh '.number_format($record->min_amount).' - KSh '.number_format($record->max_amount))
                    ->icon('heroicon-o-arrows-right-left')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->tooltip(fn ($record) => 'Created: '.$record->created_at->format('F j, Y \a\t g:i A')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                Tables\Filters\SelectFilter::make('transaction_type')
                    ->label('Transaction Type')
                    ->options(PRFTransactionType::getOptions())
                    ->placeholder('All Types'),

                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('min_charge')
                            ->label('Minimum Charge')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_charge')
                            ->label('Maximum Charge')
                            ->numeric(),
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
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view transfer rate')),
                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit transfer rate')),
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicate Rate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function ($record) {
                            $newRate = $record->replicate();
                            $newRate->min_amount = $record->max_amount + 1;
                            $newRate->max_amount = $record->max_amount + 1000;
                            $newRate->save();
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('create transfer rate')),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete transfer rate')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete transfer rate')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete transfer rate')),
                    Tables\Actions\BulkAction::make('update_charges')
                        ->label('Update Charges')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('new_charge')
                                ->label('New Charge Amount')
                                ->required()
                                ->numeric()
                                ->prefix('KSh'),
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
            ->defaultSort('min_amount', 'asc');
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
            'index' => Pages\ListTransferRates::route('/'),
            'create' => Pages\CreateTransferRate::route('/create'),
            'view' => Pages\ViewTransferRate::route('/{record}'),
            'edit' => Pages\EditTransferRate::route('/{record}/edit'),
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
