<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use App\Enums\PRFResponsibleDesk;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Cheesegrits\FilamentGoogleMaps\Fields\Geocomplete;
use Cheesegrits\FilamentGoogleMaps\Fields\Map;
use Filament\Forms\Components\Repeater;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\BulkAction;
use App\Filament\Resources\PRFEventResource\RelationManagers\EventSubscriptionsRelationManager;
use App\Filament\Resources\PRFEventResource\RelationManagers\WeatherForecastsRelationManager;
use App\Filament\Resources\PRFEventResource\Pages\ListPRFEvents;
use App\Filament\Resources\PRFEventResource\Pages\CreatePRFEvent;
use App\Filament\Resources\PRFEventResource\Pages\ViewPRFEvent;
use App\Filament\Resources\PRFEventResource\Pages\EditPRFEvent;
use App\Enums\PRFActiveStatus;
use App\Enums\PRFEventType;
use App\Filament\Resources\PRFEventResource\Pages;
use App\Filament\Resources\PRFEventResource\RelationManagers;
use App\Helpers\Utils;
use App\Models\PRFEvent;
use Cheesegrits\FilamentGoogleMaps;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PRFEventResource extends Resource
{
    protected static ?string $model = PRFEvent::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string | \UnitEnum | null $navigationGroup = 'Organising Secretary';

    protected static ?string $modelLabel = 'Event';

    protected static ?string $pluralModelLabel = 'Events';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationTooltip = 'Manage PRF events and gatherings';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Event Media')
                    ->description('Upload event poster and photos')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make(PRFEvent::EVENT_POSTERS)
                            ->label('Event Poster')
                            ->collection(PRFEvent::EVENT_POSTERS)
                            ->disk(config('filament.default_filesystem_disk'))
                            ->helperText('Upload the main poster for this event')
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make(PRFEvent::EVENT_PHOTOS)
                            ->label('Event Photos')
                            ->multiple()
                            ->collection(PRFEvent::EVENT_PHOTOS)
                            ->disk(config('filament.default_filesystem_disk'))
                            ->helperText('Upload additional photos for this event')
                            ->columnSpanFull(),
                    ]),

                Section::make('Event Details')
                    ->description('Basic event information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        TextInput::make('name')
                            ->label('Event Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter a descriptive name for this event')
                            ->placeholder('e.g., Annual Conference, Prayer Meeting'),

                        Select::make('responsible_desk')
                            ->label('🏢 Responsible Desk')
                            ->options(PRFResponsibleDesk::getOptions())
                            ->required()
                            ->placeholder('Select desk...')
                            ->helperText('The desk handling this event'),

                        Select::make('event_type')
                            ->label('Event Type')
                            ->required()
                            ->options(PRFEventType::getOptions())
                            ->helperText('Set the type of this event.'),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options(PRFActiveStatus::getOptions())
                            ->default(PRFActiveStatus::ACTIVE->value)
                            ->helperText('Set the current status of this event')
                            ->hiddenOn('create'),

                        Textarea::make('description')
                            ->label('Event Description')
                            ->required()
                            ->rows(4)
                            ->helperText('Provide a detailed description of the event')
                            ->placeholder('Describe what this event is about, its purpose, and what attendees can expect...')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Date & Time')
                    ->description('Event schedule and timing')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        DatePicker::make('start_date')
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

                        TimePicker::make('start_time')
                            ->label('Start Time')
                            ->seconds(false)
                            ->native(false)
                            ->required()
                            ->default('08:00')
                            ->helperText('Select the event start time'),

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->native(false)
                            ->timezone(Auth::user()->timezone)
                            ->afterOrEqual('start_date')
                            ->required()

                            ->helperText('Select the event end date'),

                        TimePicker::make('end_time')
                            ->label('End Time')
                            ->seconds(false)
                            ->native(false)
                            ->required()
                            ->default('17:00')
                            ->helperText('Select the event end time'),
                    ])
                    ->columns(2),

                Section::make('Venue Information')
                    ->description('Event location and capacity')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Geocomplete::make('location_search')
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

                        TextInput::make('venue')
                            ->label('🏢 Venue Name/Address')
                            ->maxLength(255)
                            ->helperText('Venue name and address (auto-filled from map search)')
                            ->placeholder('e.g., PRF Centre, Lagos'),

                        TextInput::make('capacity')
                            ->label('👥 Event Capacity')
                            ->default(0)
                            ->numeric()
                            ->helperText('Enter maximum number of attendees (0 for unlimited)')
                            ->placeholder('e.g., 100')
                            ->prefixIcon('heroicon-o-users'),

                        Map::make('location')
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

                Section::make('Weather & Recommendations')
                    ->description('AI-generated recommendations based on weather')
                    ->icon('heroicon-o-cloud')
                    ->schema([
                        Textarea::make('dressing_recommendations')
                            ->label('Dressing Recommendations')
                            ->hint('Filled in by Gemini based on the weather')
                            ->rows(4)
                            ->disabled(true)
                            ->helperText('AI-generated recommendations will appear here based on weather forecast')
                            ->columnSpanFull(),
                    ]),

                Section::make('Notification Settings')
                    ->description('Configure who receives notifications for event subscriptions')
                    ->icon('heroicon-o-bell')
                    ->schema([
                        Repeater::make('eventHandlers')
                            ->label('📢 Notification Recipients')
                            ->helperText('Select members who will receive notifications when someone subscribes to this event')
                            ->relationship()
                            ->schema([
                                Select::make('member_id')
                                    ->label('Member')
                                    ->helperText('Choose a member to receive subscription notifications')
                                    ->relationship('member', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            ])
                            ->addActionLabel('Add Notification Recipient')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->deleteAction(
                                fn (Action $action) => $action
                                    ->requiresConfirmation()
                            )
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Event Name')
                    ->description(fn ($record) => $record->venue)
                    ->icon('heroicon-o-calendar-days')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable(),

                TextColumn::make('start_time')
                    ->label('Start Time')
                    ->time('g:i A')
                    ->timezone(Auth::user()->timezone),

                TextColumn::make('capacity')
                    ->label('Capacity')
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state === 0 ? 'Unlimited' : $state)
                    ->icon('heroicon-o-users'),

                TextColumn::make('venue')
                    ->label('Venue')
                    ->icon('heroicon-o-map-pin')
                    ->limit(30)
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn ($record) => $record->venue),

                TextColumn::make('event_subscriptions_count')
                    ->label('Registrations')
                    ->counts('eventSubscriptions')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-user-group')
                    ->tooltip('Number of people registered for this event'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PRFActiveStatus::fromValue($state)->getLabel())
                    ->color(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'success' : 'danger')
                    ->icon(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Created: '.$record->created_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(PRFActiveStatus::getOptions())
                    ->placeholder('All Statuses'),

                Filter::make('upcoming')
                    ->label('Upcoming Events')
                    ->query(fn ($query) => $query->where('start_date', '>=', today()))
                    ->default(),

                Filter::make('past')
                    ->label('Past Events')
                    ->query(fn ($query) => $query->where('end_date', '<', today())),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view event')),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit event')),
                    Action::make('toggle_status')
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete event')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete event')),
                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete event')),
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['status' => PRFActiveStatus::ACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit event')),
                    BulkAction::make('deactivate')
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
            EventSubscriptionsRelationManager::class,
            WeatherForecastsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPRFEvents::route('/'),
            'create' => CreatePRFEvent::route('/create'),
            'view' => ViewPRFEvent::route('/{record}'),
            'edit' => EditPRFEvent::route('/{record}/edit'),
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
