<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\PRFEventResource\Pages;
use App\Filament\Resources\PRFEventResource\RelationManagers;
use App\Helpers\Utils;
use App\Models\PRFEvent;
use Cheesegrits\FilamentGoogleMaps;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PRFEventResource extends Resource
{
    protected static ?string $model = PRFEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Organising Secretary';

    protected static ?string $modelLabel = 'Event';

    protected static ?string $pluralModelLabel = 'Events';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationTooltip = 'Manage PRF events and gatherings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Event Media')
                    ->description('Upload event poster and photos')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make(PRFEvent::EVENT_POSTERS)
                            ->label('Event Poster')
                            ->collection(PRFEvent::EVENT_POSTERS)
                            ->disk(config('filament.default_filesystem_disk'))
                            ->helperText('Upload the main poster for this event')
                            ->columnSpanFull(),

                        Forms\Components\SpatieMediaLibraryFileUpload::make(PRFEvent::EVENT_PHOTOS)
                            ->label('Event Photos')
                            ->multiple()
                            ->collection(PRFEvent::EVENT_PHOTOS)
                            ->disk(config('filament.default_filesystem_disk'))
                            ->helperText('Upload additional photos for this event')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Event Details')
                    ->description('Basic event information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('ulid')
                            ->required()
                            ->label('ULID')
                            ->visible(app()->isLocal())
                            ->disabled(),

                        Forms\Components\TextInput::make('name')
                            ->label('Event Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter a descriptive name for this event')
                            ->placeholder('e.g., Annual Conference, Prayer Meeting'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options(PRFActiveStatus::getOptions())
                            ->default(PRFActiveStatus::ACTIVE->value)
                            ->helperText('Set the current status of this event')
                            ->hiddenOn('create'),

                        Forms\Components\Textarea::make('description')
                            ->label('Event Description')
                            ->required()
                            ->rows(4)
                            ->helperText('Provide a detailed description of the event')
                            ->placeholder('Describe what this event is about, its purpose, and what attendees can expect...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Date & Time')
                    ->description('Event schedule and timing')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->native(false)
                            ->timezone(Auth::user()->timezone)
                            ->after(today())
                            ->required()
                            ->helperText('Select the event start date')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Auto-set end_date if not already set
                                if ($state) {
                                    $set('end_date', $state);
                                }
                            }),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Start Time')
                            ->seconds(false)
                            ->native(false)
                            ->required()
                            ->default('08:00')
                            ->helperText('Select the event start time'),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->native(false)
                            ->timezone(Auth::user()->timezone)
                            ->afterOrEqual('start_date')
                            ->required()

                            ->helperText('Select the event end date'),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('End Time')
                            ->seconds(false)
                            ->native(false)
                            ->required()
                            ->default('17:00')
                            ->helperText('Select the event end time'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Venue Information')
                    ->description('Event location and capacity')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        FilamentGoogleMaps\Fields\Geocomplete::make('location_search')
                            ->label('🔍 Search for Event Venue')
                            ->helperText('Type the venue name or address to automatically find and set its location')
                            ->isLocation()
                            ->types([
                                'point_of_interest',
                                'premise',
                                'church',
                                'place_of_worship',
                                'tourist_attraction',
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
                            ->placeholder('Type venue name or address...')
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
                                    $set('venue', $elaborateAddress);
                                }
                            }),

                        Forms\Components\TextInput::make('venue')
                            ->label('🏢 Venue Name/Address')
                            ->maxLength(255)
                            ->helperText('Venue name and address (auto-filled from map search)')
                            ->placeholder('e.g., PRF Centre, Lagos'),

                        Forms\Components\TextInput::make('capacity')
                            ->label('👥 Event Capacity')
                            ->default(0)
                            ->numeric()
                            ->helperText('Enter maximum number of attendees (0 for unlimited)')
                            ->placeholder('e.g., 100')
                            ->prefixIcon('heroicon-o-users'),

                        FilamentGoogleMaps\Fields\Map::make('location')
                            ->label('📍 Interactive Event Location Map')
                            ->helperText('Click and drag to adjust the event location pin')
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
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Weather & Recommendations')
                    ->description('AI-generated recommendations based on weather')
                    ->icon('heroicon-o-cloud')
                    ->schema([
                        Forms\Components\Textarea::make('dressing_recommendations')
                            ->label('Dressing Recommendations')
                            ->hint('Filled in by Gemini based on the weather')
                            ->rows(4)
                            ->disabled(true)
                            ->helperText('AI-generated recommendations will appear here based on weather forecast')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Event Name')
                    ->description(fn ($record) => $record->venue)
                    ->icon('heroicon-o-calendar-days')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Start Time')
                    ->time('g:i A')
                    ->timezone(Auth::user()->timezone),

                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacity')
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state === 0 ? 'Unlimited' : $state)
                    ->icon('heroicon-o-users'),

                Tables\Columns\TextColumn::make('venue')
                    ->label('Venue')
                    ->icon('heroicon-o-map-pin')
                    ->limit(30)
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn ($record) => $record->venue),

                Tables\Columns\TextColumn::make('event_subscriptions_count')
                    ->label('Registrations')
                    ->counts('eventSubscriptions')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-user-group')
                    ->tooltip('Number of people registered for this event'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PRFActiveStatus::fromValue($state)->getLabel())
                    ->color(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'success' : 'danger')
                    ->icon(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
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

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(PRFActiveStatus::getOptions())
                    ->placeholder('All Statuses'),

                Tables\Filters\Filter::make('upcoming')
                    ->label('Upcoming Events')
                    ->query(fn ($query) => $query->where('start_date', '>=', today()))
                    ->default(),

                Tables\Filters\Filter::make('past')
                    ->label('Past Events')
                    ->query(fn ($query) => $query->where('end_date', '<', today())),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view event')),
                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit event')),
                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn ($record) => $record->status === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                        ->icon(fn ($record) => $record->status === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                        ->color(fn ($record) => $record->status === PRFActiveStatus::ACTIVE->value ? 'danger' : 'success')
                        ->action(function ($record) {
                            $record->update([
                                'status' => $record->status === PRFActiveStatus::ACTIVE->value ? PRFActiveStatus::INACTIVE->value : PRFActiveStatus::ACTIVE->value,
                            ]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit event')),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete event')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete event')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete event')),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['status' => PRFActiveStatus::ACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit event')),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['status' => PRFActiveStatus::INACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit event')),
                ])->visible(fn () => userCan('delete event')),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EventSubscriptionsRelationManager::class,
            RelationManagers\WeatherForecastsRelationManager::class,
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
