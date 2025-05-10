<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PrayerRequest;
use Filament\Resources\Resource;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PrayerRequestResource\Pages;
use Filament\Forms\Components\Select;

class PrayerRequestResource extends Resource
{
    protected static ?string $model = PrayerRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Prayer Secretary';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('member_id')
                    ->relationship('member', 'first_name')
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('description')
                    ->required()

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('member.first_name')
                    ->label('Member Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(
                        fn(Tables\Columns\TextColumn $column): ?string => $column->getState()

                    ),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(
                        fn(Tables\Columns\TextColumn $column): ?string => $column->getState()

                    ),
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
            'index' => Pages\ListPrayerRequests::route('/'),
            'create' => Pages\CreatePrayerRequest::route('/create'),
            'edit' => Pages\EditPrayerRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
