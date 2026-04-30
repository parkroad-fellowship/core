<?php

namespace App\Filament\Resources\PRFEvents;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFEventType;
use App\Enums\PRFResponsibleDesk;
use App\Filament\Forms\Schemas\AIRecommendationsSchema;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\DateTimeSchema;
use App\Filament\Forms\Schemas\LocationSchema;
use App\Filament\Forms\Schemas\MediaSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\PRFEvents\Pages\CreatePRFEvent;
use App\Filament\Resources\PRFEvents\Pages\EditPRFEvent;
use App\Filament\Resources\PRFEvents\Pages\ListPRFEvents;
use App\Filament\Resources\PRFEvents\Pages\ViewPRFEvent;
use App\Filament\Resources\PRFEvents\RelationManagers\EventSubscriptionsRelationManager;
use App\Filament\Resources\PRFEvents\RelationManagers\WeatherForecastsRelationManager;
use App\Models\PRFEvent;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PRFEventResource extends Resource
{
    protected static ?string $model = PRFEvent::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Organising Secretary';

    protected static ?string $modelLabel = 'Event';

    protected static ?string $pluralModelLabel = 'Events';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationTooltip = 'Manage PRF events and gatherings';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Media Section - Event images and promotional materials
                Section::make('Event Images')
                    ->description('Upload promotional images for this event. These will be displayed to attendees.')
                    ->icon('heroicon-o-photo')
                    ->collapsible()
                    ->schema([
                        MediaSchema::posterField(
                            collection: PRFEvent::EVENT_POSTERS,
                            label: 'Event Poster',
                            helperText: 'Upload the main promotional poster for this event. This image will be shown on the event listing page. Recommended size: 1200x630 pixels.',
                        ),

                        MediaSchema::uploadField(
                            collection: PRFEvent::EVENT_PHOTOS,
                            label: 'Additional Photos',
                            multiple: true,
                            maxFiles: 10,
                            helperText: 'Upload additional photos to showcase the event venue, past events, or promotional material. You can upload up to 10 images.',
                        ),
                    ]),

                // Basic Event Details Section
                Section::make('Event Details')
                    ->description('Enter the basic information about your event. This helps attendees understand what the event is about.')
                    ->icon('heroicon-o-information-circle')
                    ->collapsible()
                    ->schema([
                        ContentSchema::nameField(
                            name: 'name',
                            label: 'Event Name',
                            placeholder: 'e.g., Annual Prayer Conference 2025',
                            required: true,
                            helperText: 'Enter a clear, descriptive name for your event. This is what attendees will see when browsing events.',
                        ),

                        StatusSchema::enumSelect(
                            name: 'responsible_desk',
                            label: 'Responsible Desk',
                            enumClass: PRFResponsibleDesk::class,
                            required: true,
                            hiddenOnCreate: false,
                            helperText: 'Select the department or desk responsible for organizing this event. This determines who receives notifications.',
                        )->placeholder('Choose the organizing desk...'),

                        StatusSchema::enumSelect(
                            name: 'event_type',
                            label: 'Event Type',
                            enumClass: PRFEventType::class,
                            required: true,
                            hiddenOnCreate: false,
                            helperText: 'Select the category that best describes this event. This helps attendees find relevant events.',
                        )->placeholder('Choose the type of event...'),

                        StatusSchema::enumSelect(
                            name: 'status',
                            label: 'Event Status',
                            enumClass: PRFActiveStatus::class,
                            default: PRFActiveStatus::ACTIVE->value,
                            required: true,
                            hiddenOnCreate: true,
                            helperText: 'Active events are visible to members. Set to Inactive to hide the event from public view.',
                        ),

                        ContentSchema::descriptionField(
                            name: 'description',
                            label: 'Event Description',
                            rows: 5,
                            required: true,
                            placeholder: 'Describe what this event is about, who should attend, what to expect, and any special instructions...',
                            helperText: 'Provide a detailed description to help potential attendees understand the purpose and value of this event.',
                        ),
                    ])
                    ->columns(3),

                // Date and Time Section
                DateTimeSchema::make(
                    sectionTitle: 'Date and Time',
                    sectionDescription: 'Set when the event starts and ends. All times are shown in your local timezone.',
                    sectionIcon: 'heroicon-o-clock',
                    collapsible: true,
                ),

                // Venue and Location Section
                LocationSchema::make(
                    sectionTitle: 'Venue and Location',
                    sectionDescription: 'Enter where the event will take place. You can search for a location or select it on the map.',
                    sectionIcon: 'heroicon-o-map-pin',
                    collapsible: true,
                    includeCapacity: true,
                ),

                // Weather and AI Recommendations Section
                AIRecommendationsSchema::weatherSection(
                    sectionTitle: 'Weather Recommendations',
                    sectionDescription: 'Our system automatically generates clothing and preparation recommendations based on the weather forecast for the event date and location.',
                    sectionIcon: 'heroicon-o-cloud',
                    collapsible: true,
                ),

                // Notification Settings Section
                Section::make('Notification Recipients')
                    ->description('Choose who should receive notifications when someone registers for this event.')
                    ->icon('heroicon-o-bell')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Repeater::make('eventHandlers')
                            ->label('People to Notify')
                            ->helperText('Add members who should receive an email or notification when someone registers for this event. You can add multiple people.')
                            ->relationship()
                            ->schema([
                                StatusSchema::relationshipSelect(
                                    name: 'member_id',
                                    label: 'Select Member',
                                    relationship: 'member',
                                    titleAttribute: 'full_name',
                                    required: true,
                                    searchable: true,
                                    preload: true,
                                    helperText: 'Type a name to search for a member. They will receive notifications for this event.',
                                )->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            ])
                            ->addActionLabel('Add Another Person')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->cloneable()
                            ->deleteAction(
                                fn (Action $action) => $action
                                    ->requiresConfirmation()
                                    ->modalHeading('Remove This Person?')
                                    ->modalDescription('Are you sure you want to remove this person from the notification list? They will no longer receive registration alerts.')
                            )
                            ->columnSpanFull(),
                    ]),
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
