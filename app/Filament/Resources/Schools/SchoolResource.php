<?php

namespace App\Filament\Resources\Schools;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFInstitutionType;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\Schools\Pages\CreateSchool;
use App\Filament\Resources\Schools\Pages\EditSchool;
use App\Filament\Resources\Schools\Pages\ListSchools;
use App\Filament\Resources\Schools\Pages\ViewSchool;
use App\Filament\Resources\Schools\RelationManagers\BudgetEstimatesRelationManager;
use App\Filament\Resources\Schools\RelationManagers\SchoolContactsRelationManager;
use App\Helpers\Utils;
use App\Jobs\School\CalculateRouteJob;
use App\Models\MissionType;
use App\Models\School;
use Cheesegrits\FilamentGoogleMaps\Fields\Geocomplete;
use Cheesegrits\FilamentGoogleMaps\Fields\Map;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Missions Secretary';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Schools';

    protected static ?string $modelLabel = 'School';

    protected static ?string $pluralModelLabel = 'Schools';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Quick Actions Section
                Section::make('Quick Actions')
                    ->description('Administrative actions for school management')
                    ->icon('heroicon-o-bolt')
                    ->schema([
                        Actions::make([
                            Action::make('re-calculate')
                                ->icon('heroicon-m-arrow-path')
                                ->color(Color::Blue)
                                ->requiresConfirmation()
                                ->label('Re-calculate Distance')
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
                    ->collapsed()
                    ->columnSpanFull(),

                // Basic Information Section
                Section::make('School Information')
                    ->description('Enter the basic details about this educational institution')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                ContentSchema::nameField(
                                    name: 'name',
                                    label: 'School Name',
                                    placeholder: 'e.g., Nairobi High School',
                                    helperText: 'Enter the official registered name of the school',
                                )
                                    ->prefixIcon('heroicon-o-academic-cap')
                                    ->live(onBlur: true),

                                TextInput::make('total_students')
                                    ->label('Total Students')
                                    ->helperText('How many students are currently enrolled at this school?')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(10000)
                                    ->placeholder('e.g., 500')
                                    ->prefixIcon('heroicon-o-users'),
                            ]),

                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                StatusSchema::enumSelect(
                                    name: 'institution_type',
                                    label: 'Institution Type',
                                    enumClass: PRFInstitutionType::class,
                                    default: PRFInstitutionType::HIGH_SCHOOL->value,
                                    required: true,
                                    hiddenOnCreate: false,
                                    helperText: 'Select the type of educational institution (e.g., Primary, High School, College)',
                                )
                                    ->prefixIcon('heroicon-o-building-library'),

                                StatusSchema::enumSelect(
                                    name: 'is_active',
                                    label: 'Status',
                                    enumClass: PRFActiveStatus::class,
                                    default: PRFActiveStatus::ACTIVE->value,
                                    required: true,
                                    hiddenOnCreate: true,
                                    helperText: 'Is this school currently available for mission visits?',
                                )
                                    ->suffixIcon('heroicon-o-check-circle'),
                            ]),

                        ContentSchema::descriptionField(
                            name: 'description',
                            label: 'Description',
                            rows: 3,
                            placeholder: 'Describe the school, its mission, student demographics, and any relevant information for planning visits...',
                            helperText: 'Provide helpful context about the school that mission teams should know',
                        ),

                        ContentSchema::descriptionField(
                            name: 'directions',
                            label: 'Directions and Access Notes',
                            rows: 3,
                            placeholder: 'e.g., Turn left at the main roundabout, school gate is 200m on the right. Public transport: Take matatu route 46...',
                            helperText: 'Include driving directions, landmarks, and public transport options to help teams find the school',
                        ),
                    ])
                    ->collapsible()
                    ->persistCollapsed()
                    ->columnSpanFull(),

                // Location Section
                Section::make('Location Information')
                    ->description('Set the school location on the map for route planning')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Geocomplete::make('location_search')
                            ->label('Search for School Location')
                            ->helperText('Start typing the school name or address to find it on the map')
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
                            ->placeholder('Type school name or address to search...')
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

                        Textarea::make('address')
                            ->label('School Address')
                            ->helperText('This address is automatically filled when you search above, but you can edit it if needed')
                            ->columnSpanFull()
                            ->required()
                            ->rows(2)
                            ->maxLength(1000)
                            ->placeholder('Full address will appear here after searching...'),

                        Map::make('location')
                            ->label('Interactive Map')
                            ->helperText('You can click and drag the pin to fine-tune the exact location')
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

                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('static_duration')
                                    ->label('Estimated Travel Time')
                                    ->helperText('Automatically calculated travel time from headquarters')
                                    ->disabled()
                                    ->placeholder('Will be calculated automatically')
                                    ->prefixIcon('heroicon-o-clock'),

                                TextInput::make('distance')
                                    ->label('Distance from Headquarters')
                                    ->helperText('Automatically calculated distance for route planning')
                                    ->disabled()
                                    ->placeholder('Will be calculated automatically')
                                    ->prefixIcon('heroicon-o-map-pin'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed()
                    ->columnSpanFull(),

                // Mission Defaults Section
                Section::make('Mission Defaults')
                    ->description('Set default values for new missions at this school. These will auto-fill when creating missions.')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TimePicker::make('mission_defaults.default_start_time')
                                    ->label('Default Start Time')
                                    ->helperText('Typical mission start time at this school')
                                    ->seconds(false)
                                    ->native(false)
                                    ->format('H:i')
                                    ->placeholder('e.g., 08:00'),

                                TimePicker::make('mission_defaults.default_end_time')
                                    ->label('Default End Time')
                                    ->helperText('Typical mission end time at this school')
                                    ->seconds(false)
                                    ->native(false)
                                    ->format('H:i')
                                    ->placeholder('e.g., 15:00'),
                            ]),

                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('mission_defaults.default_capacity')
                                    ->label('Default Team Size')
                                    ->helperText('Typical number of missionaries needed for this school')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->placeholder('e.g., 10')
                                    ->prefixIcon('heroicon-o-users'),

                                Select::make('mission_defaults.default_mission_type_id')
                                    ->label('Default Mission Type')
                                    ->helperText('Typical type of mission conducted at this school')
                                    ->options(fn () => MissionType::query()
                                        ->where('is_active', PRFActiveStatus::ACTIVE)
                                        ->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->placeholder('Select mission type...'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->persistCollapsed()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('School Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->color(Color::Blue)
                    ->wrap()
                    ->tooltip('School name and address')
                    ->description(fn ($record) => $record->address ?
                        Str::limit($record->address, 50) : 'No address set'),

                TextColumn::make('institution_type')
                    ->label('Type')
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

                TextColumn::make('total_students')
                    ->label('Students')
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

                TextColumn::make('missions_count')
                    ->label('Missions')
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

                TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Gray)
                    ->tooltip('Date school was registered'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Gray)
                    ->tooltip('Last modification date'),

                TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Red)
                    ->tooltip('Date school was deleted'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Show Deleted')
                    ->placeholder('Active schools only')
                    ->trueLabel('With deleted')
                    ->falseLabel('Active only'),

                SelectFilter::make('is_active')
                    ->label('Status Filter')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active Schools',
                        PRFActiveStatus::INACTIVE->value => 'Inactive Schools',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->indicator('Status'),

                SelectFilter::make('institution_type')
                    ->label('Institution Type')
                    ->options(PRFInstitutionType::getOptions())
                    ->indicator('Type'),

                Filter::make('has_distance')
                    ->label('Distance Calculated')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('distance'))
                    ->indicator('With Distance'),

                Filter::make('no_missions')
                    ->label('No Missions')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('missions'))
                    ->indicator('No Missions'),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color(Color::Gray)
                        ->visible(fn () => userCan('view school')),

                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color(Color::Orange)
                        ->visible(fn () => userCan('edit school'))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('School updated!')
                                ->body('School information has been updated successfully.')
                        ),

                    Action::make('calculate_distance')
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

                    Action::make('toggle_status')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? Color::Red : Color::Green)
                        ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                        ->action(function ($record) {
                            $record->update(['is_active' => ! $record->is_active]);
                            $status = $record->is_active ? 'activated' : 'deactivated';
                            Notification::make()
                                ->success()
                                ->title('Status updated!')
                                ->body("School has been {$status} successfully.")
                                ->send();
                        })
                        ->visible(fn () => userCan('edit school'))
                        ->requiresConfirmation(),

                    DeleteAction::make()
                        ->color(Color::Red)
                        ->visible(fn () => userCan('delete school')),

                    RestoreAction::make()
                        ->color(Color::Green)
                        ->visible(fn () => userCan('delete school')),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('calculate_distances')
                        ->label('Calculate Distances')
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

                    BulkAction::make('activate_schools')
                        ->label('Activate Selected')
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

                    BulkAction::make('deactivate_schools')
                        ->label('Deactivate Selected')
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

                    DeleteBulkAction::make()
                        ->color(Color::Red),

                    ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    RestoreBulkAction::make()
                        ->color(Color::Green),
                ])->visible(fn () => userCan('delete school')),
            ])
            ->defaultSort('name', 'asc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->searchPlaceholder('Search schools by name or address...')
            ->emptyStateHeading('No schools found')
            ->emptyStateDescription('Start by adding your first school to the system.')
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->recordClasses(fn ($record) => match (true) {
                ! $record->is_active => 'bg-red-50 border-l-4 border-red-400',
                ! $record->distance => 'bg-yellow-50 border-l-4 border-yellow-400',
                $record->trashed() => 'bg-gray-50 border-l-4 border-gray-400',
                default => null,
            });
    }

    public static function getRelations(): array
    {
        return [
            SchoolContactsRelationManager::class,
            BudgetEstimatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchools::route('/'),
            'create' => CreateSchool::route('/create'),
            'view' => ViewSchool::route('/{record}'),
            'edit' => EditSchool::route('/{record}/edit'),
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
