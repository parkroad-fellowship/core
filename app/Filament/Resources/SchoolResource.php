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
use Filament\Support\Colors\Color;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = '🎯 Mission Schools';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Schools';

    protected static ?string $modelLabel = 'School';

    protected static ?string $pluralModelLabel = 'Schools';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Quick Actions Section
                Forms\Components\Section::make('⚡ Quick Actions')
                    ->description('Administrative actions for school management')
                    ->icon('heroicon-o-bolt')
                    ->schema([
                        Actions::make([
                            Action::make('re-calculate')
                                ->icon('heroicon-m-arrow-path')
                                ->color(Color::Blue)
                                ->requiresConfirmation()
                                ->label('🔄 Re-calculate Distance')
                                ->action(function ($record, $data) {
                                    CalculateRouteJob::dispatch($record);
                                    Notification::make()
                                        ->title('Distance calculation started')
                                        ->body('Route distance and time will be updated shortly.')
                                        ->info()
                                        ->send();
                                })
                                ->visible(fn ($record) => $record?->exists),
                        ])->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record?->exists)
                    ->collapsible()
                    ->collapsed(),

                // Basic Information Section
                Forms\Components\Section::make('🏫 School Information')
                    ->description('Basic school details and classification')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('🏫 School Name')
                                    ->helperText('Official name of the educational institution')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Nairobi High School')
                                    ->prefixIcon('heroicon-o-academic-cap')
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('total_students')
                                    ->label('👥 Total Students')
                                    ->helperText('Current student enrollment')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(10000)
                                    ->placeholder('e.g., 500')
                                    ->prefixIcon('heroicon-o-users'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('institution_type')
                                    ->label('🏛️ Institution Type')
                                    ->helperText('Classification of the educational institution')
                                    ->required()
                                    ->options(PRFInstitutionType::getOptions())
                                    ->default(PRFInstitutionType::HIGH_SCHOOL->value)
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-building-library'),

                                Forms\Components\Select::make('is_active')
                                    ->label('📊 Status')
                                    ->helperText('School availability for missions')
                                    ->required()
                                    ->options(PRFActiveStatus::getOptions())
                                    ->default(PRFActiveStatus::ACTIVE->value)
                                    ->disabledOn('create')
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-check-circle'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('📝 Description')
                            ->helperText('Brief description of the school, its mission, and relevant information')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Describe the school, its mission, and any other relevant information...'),

                        Forms\Components\Textarea::make('directions')
                            ->label('🧭 Directions')
                            ->helperText('Additional directions, notes about location, and public transport access')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Provide directions to the school and public transport information...'),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // Location Section
                Forms\Components\Section::make('📍 Location Information')
                    ->description('Geographic location and route planning')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        FilamentGoogleMaps\Fields\Geocomplete::make('location_search')
                            ->label('🔍 Search for School Location')
                            ->helperText('Type the school name or address to automatically find and set its location')
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
                            ->placeholder('Type school name or address...')
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
                            ->label('🏠 School Address')
                            ->helperText('Complete address of the school (auto-filled from map search)')
                            ->columnSpanFull()
                            ->required()
                            ->rows(2)
                            ->maxLength(1000)
                            ->placeholder('Complete school address...'),

                        FilamentGoogleMaps\Fields\Map::make('location')
                            ->label('📍 Interactive Map')
                            ->helperText('Click and drag to adjust the school location pin')
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

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('static_duration')
                                    ->label('⏱️ Travel Time')
                                    ->helperText('Estimated travel time from headquarters')
                                    ->disabled()
                                    ->placeholder('Auto-calculated')
                                    ->prefixIcon('heroicon-o-clock'),

                                Forms\Components\TextInput::make('distance')
                                    ->label('📏 Distance')
                                    ->helperText('Distance from headquarters')
                                    ->disabled()
                                    ->placeholder('Auto-calculated')
                                    ->prefixIcon('heroicon-o-map-pin'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('🏫 School Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->color(Color::Blue)
                    ->wrap()
                    ->tooltip('School name and address')
                    ->description(fn ($record) => $record->address ? 
                        \Illuminate\Support\Str::limit($record->address, 50) : 'No address set'),

                Tables\Columns\TextColumn::make('institution_type')
                    ->label('🏛️ Type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'PRIMARY_SCHOOL' => 'success',
                        'HIGH_SCHOOL' => 'warning',
                        'COLLEGE' => 'info',
                        'UNIVERSITY' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        1 => 'High School',
                        2 => 'Primary School', 
                        3 => 'College',
                        4 => 'University',
                        5 => 'Community',
                        6 => 'Junior Secondary School',
                        default => 'Unknown'
                    })
                    ->tooltip('Type of educational institution'),

                Tables\Columns\TextColumn::make('total_students')
                    ->label('👥 Students')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === 0 => 'gray',
                        $state <= 100 => 'warning',
                        $state <= 500 => 'info',
                        default => 'success',
                    })
                    ->icon('heroicon-o-users')
                    ->tooltip('Total student enrollment'),

                Tables\Columns\TextColumn::make('missions_count')
                    ->label('🎯 Missions')
                    ->counts('missions')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === 0 => 'gray',
                        $state <= 5 => 'warning',
                        $state <= 10 => 'info',
                        default => 'success',
                    })
                    ->icon('heroicon-o-map-pin')
                    ->tooltip('Number of missions conducted'),

    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Gray)
                    ->tooltip('Date school was registered'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Gray)
                    ->tooltip('Last modification date'),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('🗑️ Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Red)
                    ->tooltip('Date school was deleted'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('🗑️ Show Deleted')
                    ->placeholder('Active schools only')
                    ->trueLabel('With deleted')
                    ->falseLabel('Active only'),

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('📊 Status Filter')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => '✅ Active Schools',
                        PRFActiveStatus::INACTIVE->value => '❌ Inactive Schools',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->indicator('Status'),

                Tables\Filters\SelectFilter::make('institution_type')
                    ->label('🏛️ Institution Type')
                    ->options(PRFInstitutionType::getOptions())
                    ->indicator('Type'),

                Tables\Filters\Filter::make('has_distance')
                    ->label('📏 Distance Calculated')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('distance'))
                    ->indicator('With Distance'),

                Tables\Filters\Filter::make('no_missions')
                    ->label('🎯 No Missions')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('missions'))
                    ->indicator('No Missions'),
            ], layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color(Color::Gray)
                        ->visible(fn () => userCan('view school')),

                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color(Color::Orange)
                        ->visible(fn () => userCan('edit school'))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('School updated!')
                                ->body('School information has been updated successfully.')
                        ),

                    Tables\Actions\Action::make('calculate_distance')
                        ->icon('heroicon-o-map-pin')
                        ->color(Color::Blue)
                        ->label('Calculate Distance')
                        ->action(function ($record) {
                            CalculateRouteJob::dispatch($record);
                            Notification::make()
                                ->success()
                                ->title('Distance calculation started!')
                                ->body('Route distance and time will be updated shortly.')
                                ->send();
                        })
                        ->visible(fn () => userCan('edit school'))
                        ->requiresConfirmation(),

                    Tables\Actions\Action::make('toggle_status')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? Color::Red : Color::Green)
                        ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                        ->action(function ($record) {
                            $record->update(['is_active' => !$record->is_active]);
                            $status = $record->is_active ? 'activated' : 'deactivated';
                            Notification::make()
                                ->success()
                                ->title('Status updated!')
                                ->body("School has been {$status} successfully.")
                                ->send();
                        })
                        ->visible(fn () => userCan('edit school'))
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteAction::make()
                        ->color(Color::Red)
                        ->visible(fn () => userCan('delete school')),

                    Tables\Actions\RestoreAction::make()
                        ->color(Color::Green)
                        ->visible(fn () => userCan('delete school')),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('calculate_distances')
                        ->label('📏 Calculate Distances')
                        ->icon('heroicon-o-map-pin')
                        ->color(Color::Blue)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => CalculateRouteJob::dispatch($record));
                            
                            Notification::make()
                                ->title('Distance calculations started')
                                ->body("Distance calculations for {$count} schools have been queued.")
                                ->info()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('activate_schools')
                        ->label('✅ Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => true]));
                            
                            Notification::make()
                                ->title('Schools activated')
                                ->body("{$count} schools have been activated successfully.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate_schools')
                        ->label('❌ Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color(Color::Red)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => false]));
                            
                            Notification::make()
                                ->title('Schools deactivated')
                                ->body("{$count} schools have been deactivated successfully.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\RestoreBulkAction::make()
                        ->color(Color::Green),
                ])->visible(fn () => userCan('delete school')),
            ])
            ->defaultSort('name', 'asc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->searchPlaceholder('🔍 Search schools by name or address...')
            ->emptyStateHeading('No schools found')
            ->emptyStateDescription('Start by adding your first school to the system.')
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->recordClasses(fn ($record) => match (true) {
                !$record->is_active => 'bg-red-50 border-l-4 border-red-400',
                !$record->distance => 'bg-yellow-50 border-l-4 border-yellow-400',
                $record->trashed() => 'bg-gray-50 border-l-4 border-gray-400',
                default => null,
            });
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
