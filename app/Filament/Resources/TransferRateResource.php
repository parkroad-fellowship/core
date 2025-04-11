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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('transaction_type')
                    ->required()
                    ->options(PRFTransactionType::getOptions()),
                Forms\Components\TextInput::make('min_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('max_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('charge')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_type')
                    ->formatStateUsing(fn (string $state): string => PRFTransactionType::fromValue($state)->getLabel()),
                Tables\Columns\TextColumn::make('min_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('charge')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
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

    // public static function canAccess(): bool
    // {
    //     return userCan('viewAny transfer rate');
    // }
}
