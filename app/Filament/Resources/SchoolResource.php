<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFInstitutionType;
use App\Filament\Resources\SchoolResource\Pages;
use App\Filament\Resources\SchoolResource\RelationManagers;
use App\Helpers\Utils;
use App\Jobs\School\CalculateRouteJob;
use App\Models\School;
use Cheesegrits\FilamentGoogleMaps;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Missions Secretary';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Actions::make([
                    Action::make('re-calculate')
                        ->icon('heroicon-m-arrow-path')
                        ->requiresConfirmation()
                        ->label('Re-calculate distance')
                        ->action(function ($record, $data) {
                            CalculateRouteJob::dispatch($record);
                        }),
                ])->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('total_students')
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('institution_type')
                    ->required()
                    ->options(PRFInstitutionType::getOptions())
                    ->default(PRFInstitutionType::HIGH_SCHOOL->value),
                Forms\Components\Select::make('is_active')
                    ->required()
                    ->options(PRFActiveStatus::getOptions())
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->disabledOn('create'),
                Forms\Components\Textarea::make('description')
                    ->hint('A brief description of the school, its mission, and any other relevant information.'),
                Forms\Components\Textarea::make('directions')
                    ->hint('Provide any additional directions or notes about the school location. Also how to access via public means.'),
                Forms\Components\Section::make('Route Information')
                    ->schema([
                        FilamentGoogleMaps\Fields\Geocomplete::make('location_search')
                            ->label('Search for the institution if the addresses is not already set')
                            ->isLocation()
                            ->types([
                                'school',
                                'point_of_interest',
                                'university',
                                'secondary_school',
                                'premise',
                            ])
                            ->reverseGeocode([
                                'street_number' => '%n',
                                'route' => '%S',
                                'locality' => '%L',
                                'sublocality' => '%sublocality',
                                'administrative_area_level_3' => '%A3',
                                'administrative_area_level_2' => '%A2',
                                'administrative_area_level_1' => '%A1',
                                'country' => '%c',
                                'postal_code' => '%z',
                                'formatted' => '%formatted_address',
                            ])
                            ->countries(['ke'])
                            ->updateLatLng()
                            ->maxLength(1024)
                            ->minChars(3)
                            ->placeholder('Type school name')
                            ->geolocate()
                            ->geolocateIcon('heroicon-o-map')
                            ->columnSpanFull()
                            ->dehydrated(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Force refresh of the map when location is updated
                                $set('location', $state);

                                // Get elaborate address using the utility function
                                if ($state && isset($state['lat']) && isset($state['lng'])) {
                                    $lat = $state['lat'];
                                    $lng = $state['lng'];
                                    $fallbackAddress = $state['formatted_address'] ?? null;

                                    $elaborateAddress = Utils::buildKenyanAddress($lat, $lng, $fallbackAddress);
                                    $set('address', $elaborateAddress);
                                }
                            }),
                        Forms\Components\Textarea::make('address')
                            ->columnSpanFull()
                            ->required()
                            ->hint('This is automatically filled when you search for the school name in the box above.'),
                        FilamentGoogleMaps\Fields\Map::make('location')
                            ->mapControls([
                                'mapTypeControl' => true,
                                'zoomControl' => true,
                                'fullscreenControl' => true,
                                'streetViewControl' => false,
                                'rotateControl' => false,
                                'scaleControl' => false,
                            ])
                            ->autocompleteReverse(true)
                            ->clickable(true)
                            ->draggable(true)
                            ->geolocate(true)
                            ->geolocateOnLoad(false)
                            ->defaultZoom(10)
                            ->defaultLocation([-1.319167, 36.9275])
                            ->height('400px')
                            ->reactive()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('static_duration')
                            ->label('Time Estimate')
                            ->disabled(true),
                        Forms\Components\TextInput::make('distance')
                            ->label('Distance Estimate')
                            ->disabled(true),
                    ])->columns(2),
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
                    ->formatStateUsing(fn($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
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
                Tables\Actions\ViewAction::make()->visible(fn() => userCan('view school')),
                Tables\Actions\EditAction::make()->visible(fn() => userCan('edit school')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ])->visible(fn() => userCan('delete school')),
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
