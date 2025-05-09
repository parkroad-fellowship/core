<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrayerRequestResource\Pages;
use App\Models\PrayerRequest;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PrayerRequestResource extends Resource
{
    protected static ?string $model = PrayerRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Prayer Secretary';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
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
                        fn (Tables\Columns\TextColumn $column): ?string => $column->getState()

                    ),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(
                        fn (Tables\Columns\TextColumn $column): ?string => $column->getState()

                    ),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
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
