<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\PRFEventResource\Pages;
use App\Filament\Resources\PRFEventResource\RelationManagers;
use App\Models\PRFEvent;
use Cheesegrits\FilamentGoogleMaps;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PRFEventResource extends Resource
{
    protected static ?string $model = PRFEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Organising Secretary';

    protected static ?string $modelLabel = 'Event';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\SpatieMediaLibraryFileUpload::make(PRFEvent::EVENT_POSTERS)
                    ->label('Poster')
                    ->columnSpanFull()
                    ->collection(PRFEvent::EVENT_POSTERS)
                    ->disk(config('media-library.disk_name')),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options(PRFActiveStatus::getOptions())
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->hiddenOn('create'),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('start_date')
                    ->after(today())
                    ->required(),
                Forms\Components\TimePicker::make('start_time')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->after(today())
                    ->required(),
                Forms\Components\TimePicker::make('end_time')
                    ->required(),
                Forms\Components\TextInput::make('venue')
                    ->maxLength(255),
                Forms\Components\TextInput::make('capacity')
                    ->hint('Leave blank if unlimited')
                    ->numeric(),
                FilamentGoogleMaps\Fields\Map::make('location')
                    ->autocompleteReverse(true)
                    ->defaultZoom(10)
                    ->defaultLocation([-1.319167, 36.9275])
                    ->columnSpanFull(),
                Forms\Components\SpatieMediaLibraryFileUpload::make(PRFEvent::EVENT_PHOTOS)
                    ->label('Event Photos')
                    ->multiple()
                    ->columnSpanFull()
                    ->collection(PRFEvent::EVENT_PHOTOS)
                    ->disk(config('media-library.disk_name')),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->time(),
                Tables\Columns\TextColumn::make('capacity')
                    ->numeric(),
                Tables\Columns\TextColumn::make('venue')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->status)->name)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->visible(userCan('view event')),
                Tables\Actions\EditAction::make()->visible(userCan('edit event')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ])->visible(userCan('delete event')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EventSubscriptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPRFEvents::route('/'),
            'create' => Pages\CreatePRFEvent::route('/create'),
            'view' => Pages\ViewPRFEvent::route('/{record}'),
            'edit' => Pages\EditPRFEvent::route('/{record}/edit'),
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
        return userCan('viewAny event');
    }
}
