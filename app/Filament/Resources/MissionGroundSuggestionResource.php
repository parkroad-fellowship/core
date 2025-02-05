<?php

namespace App\Filament\Resources;

use App\Enums\PRFMissionGroundSuggestionStatus;
use App\Filament\Resources\MissionGroundSuggestionResource\Pages;
use App\Models\MissionGroundSuggestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class MissionGroundSuggestionResource extends Resource
{
    protected static ?string $model = MissionGroundSuggestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Missions Secretary';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('suggestor_id')
                    ->required()
                    ->relationship('suggestor', 'first_name'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_person')
                    ->required()
                    ->maxLength(255),
                PhoneInput::make('contact_number')
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->required()
                    ->columnSpanFull()
                    ->hiddenOn('create'),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options(PRFMissionGroundSuggestionStatus::getOptions())
                    ->default(PRFMissionGroundSuggestionStatus::PENDING->value)
                    ->hiddenOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('suggestor.first_name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact_person')
                    ->searchable(),
                PhoneColumn::make('contact_number')
                    ->displayFormat(PhoneInputNumberType::INTERNATIONAL),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options(PRFMissionGroundSuggestionStatus::getOptions())
                    ->label('Status'),
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
            'index' => Pages\ListMissionGroundSuggestions::route('/'),
            'create' => Pages\CreateMissionGroundSuggestion::route('/create'),
            'view' => Pages\ViewMissionGroundSuggestion::route('/{record}'),
            'edit' => Pages\EditMissionGroundSuggestion::route('/{record}/edit'),
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
        return userCan('viewAny mission ground suggestion');
    }
}
