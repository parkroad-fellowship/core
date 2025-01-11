<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFInstitutionType;
use App\Filament\Resources\SchoolResource\Pages;
use App\Filament\Resources\SchoolResource\RelationManagers;
use App\Models\School;
use Cheesegrits\FilamentGoogleMaps;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Missions Secretary';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('total_students')
                    ->numeric()
                    ->default(0),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('address')
                    ->required(),
                Forms\Components\Textarea::make('directions')
                    ->required(),
                FilamentGoogleMaps\Fields\Map::make('location')
                    ->autocompleteReverse(true)
                    ->defaultZoom(10)
                    ->defaultLocation([-1.319167, 36.9275])
                    ->columnSpanFull(),
                // FilamentGoogleMaps\Fields\Geocomplete::make('location')
                //     ->isLocation()
                //     ->reverseGeocode([
                //         'city'   => '%L',
                //         'zip'    => '%z',
                //         'state'  => '%A1',
                //         'street' => '%n %S',
                //     ])
                //     ->countries(['ke'])
                //     ->debug()
                //     ->updateLatLng()
                //     ->maxLength(1024)
                //     ->minChars(0)
                //     ->prefix('Choose:')
                //     ->placeholder('Start typing an address ...')
                //     ->geolocate()
                //     ->geolocateIcon('heroicon-o-map'),
                Forms\Components\Select::make('institution_type')
                    ->required()
                    ->options(PRFInstitutionType::getOptions())
                    ->default(PRFInstitutionType::HIGH_SCHOOL->value),
                Forms\Components\Select::make('is_active')
                    ->required()
                    ->options(PRFActiveStatus::getOptions())
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->hiddenOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_students')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
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
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->visible(fn () => userCan('view school')),
                Tables\Actions\EditAction::make()->visible(fn () => userCan('edit school')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ])->visible(fn () => userCan('delete school')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SchoolContactsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchools::route('/'),
            'create' => Pages\CreateSchool::route('/create'),
            'view' => Pages\ViewSchool::route('/{record}'),
            'edit' => Pages\EditSchool::route('/{record}/edit'),
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
        return userCan('viewAny school');
    }
}
