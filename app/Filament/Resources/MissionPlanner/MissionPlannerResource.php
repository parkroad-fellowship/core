<?php

namespace App\Filament\Resources\MissionPlanner;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Filament\Forms\Schemas\AIRecommendationsSchema;
use App\Filament\Forms\Schemas\ContactSchema;
use App\Filament\Forms\Schemas\DateTimeSchema;
use App\Filament\Forms\Schemas\MediaSchema;
use App\Filament\Forms\Schemas\SchoolSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\MissionPlanner\Pages\CreateMission;
use App\Filament\Resources\MissionPlanner\Pages\EditMission;
use App\Filament\Resources\MissionPlanner\Pages\ListMissions;
use App\Filament\Resources\MissionPlanner\Pages\ViewMission;
use App\Filament\Resources\Missions\RelationManagers\AccountingEventRelationManager;
use App\Filament\Resources\Missions\RelationManagers\DebriefNotesRelationManager;
use App\Filament\Resources\Missions\RelationManagers\MissionQuestionsRelationManager;
use App\Filament\Resources\Missions\RelationManagers\MissionSessionsRelationManager;
use App\Filament\Resources\Missions\RelationManagers\MissionSubscriptionsRelationManager;
use App\Filament\Resources\Missions\RelationManagers\RequisitionsRelationManager;
use App\Filament\Resources\Missions\RelationManagers\SMSLogsRelationManager;
use App\Filament\Resources\Missions\RelationManagers\SoulsRelationManager;
use App\Filament\Resources\Missions\RelationManagers\WeatherForecastsRelationManager;
use App\Jobs\AccountingEvent\EmailFinancialReportJob;
use App\Jobs\AccountingEvent\MakeZeroRequisitionJob;
use App\Jobs\Mission\ApproveJob;
use App\Jobs\Mission\GenerateExecutiveSummaryJob;
use App\Jobs\Mission\NotifySchoolOfMissionJob;
use App\Jobs\Mission\NotifyWhatsAppGroupJob;
use App\Jobs\Mission\RejectJob;
use App\Jobs\Mission\RequestSchoolFeedbackJob;
use App\Jobs\Mission\UploadFilesToDriveJob;
use App\Models\Mission;
use App\Models\School;
use App\Models\SchoolTerm;
use App\Models\User;
use App\Services\MissionDefaultsService;
use App\Services\Missions\MissionProgress;
use App\States\Mission\MissionState;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class MissionPlannerResource extends Resource
{
    protected static ?string $model = Mission::class;

    protected static ?string $slug = 'mission-planner';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|\UnitEnum|null $navigationGroup = 'Missions Secretary';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Mission';

    protected static ?string $pluralModelLabel = 'Missions';

    protected static ?string $navigationLabel = 'Missions (new)';

    protected static ?string $navigationTooltip = 'Plan missions to schools and follow them through';

    public static function getModelLabel(): string
    {
        return 'Mission';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Missions';
    }

    protected static int $globalSearchResultsLimit = 20;

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        assert($record instanceof Mission);

        return implode(' – ', array_filter([
            $record->school?->name,
            $record->missionType?->name,
            $record->start_date->format('j M Y'),
        ]));
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        assert($record instanceof Mission);

        return [
            'Status' => $record->status->getLabel(),
            'Theme' => (string) $record->theme,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['theme', 'school.name', 'missionType.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Mission::query()->where('status', PRFMissionStatus::PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Missions waiting for approval';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // Main Tabs Layout
            Tabs::make('Mission')
                ->tabs([
                    // Tab 1: Overview (Core mission info - visible on create and edit)
                    Tab::make('Overview')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            static::getMissionDetailsSection(),
                            static::getScheduleSection(),
                            static::getTeamSection(),
                        ]),

                    // Tab 2: School (School info - visible on edit)
                    Tab::make('Preparation')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->badge(fn(?Mission $record) => $record instanceof Mission
                            && !$record->status->is(PRFMissionStatus::SERVICED)
                            && !$record->mission_prep_notes
                                ? '!'
                                : null)
                        ->badgeColor('warning')
                        ->schema([
                            static::getPreparationSection(),
                            static::getCommunicationSection(),
                        ])
                        ->visible(fn(?Mission $record) => $record?->exists),

                    // Tab 4: Summary & Media (Post-mission - visible after serviced)
                    Tab::make('Summary & Media')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            static::getMissionContentSection(),
                            static::getMediaSection(),
                        ])
                        // Photos and the summary are needed to complete a mission, so they can be
                        // added from approval onwards.
                        ->visible(
                            fn(?Mission $record) => $record instanceof Mission
                            && $record->status->is(...[
                                PRFMissionStatus::APPROVED,
                                PRFMissionStatus::FULLY_SUBSCRIBED,
                                PRFMissionStatus::POSTPONED,
                                PRFMissionStatus::SERVICED,
                            ]),
                        ),
                ])
                ->persistTabInQueryString()
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Callout::make(fn(Mission $record): string => 'Mission ' . strtolower($record->status->getLabel()))
                ->description(fn(Mission $record): ?string => $record->status_reason)
                ->status(fn(Mission $record): string => $record->status->is(PRFMissionStatus::POSTPONED)
                    ? 'warning'
                    : 'danger')
                ->visible(fn(Mission $record): bool => $record->status->is(...[
                    PRFMissionStatus::POSTPONED,
                    PRFMissionStatus::CANCELLED,
                    PRFMissionStatus::REJECTED,
                ]))
                ->columnSpanFull(),

            Section::make('Mission Summary')
                ->columnSpanFull()
                ->icon('heroicon-o-information-circle')
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('school.name')
                            ->label('School')
                            ->weight('bold')
                            ->icon('heroicon-o-academic-cap'),
                        TextEntry::make('missionType.name')->label('Mission Type')->badge()->color('info'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn(MissionState $state): string => $state->getLabel())
                            ->color(fn(MissionState $state): string => $state->getColor()),
                        TextEntry::make('schoolTerm.name')->label('School Term'),
                        TextEntry::make('theme')->label('Theme')->columnSpanFull(),
                        TextEntry::make('start_date')
                            ->label('Starts')
                            ->date('D j M Y')
                            ->suffix(fn(Mission $record): string => $record->start_time
                                ? ", {$record->start_time}"
                                : ''),
                        TextEntry::make('end_date')
                            ->label('Ends')
                            ->date('D j M Y')
                            ->suffix(fn(Mission $record): string => $record->end_time ? ", {$record->end_time}" : ''),
                        TextEntry::make('capacity')->label('Missioners needed')->numeric(),
                        TextEntry::make('approved_team')
                            ->label('Team approved')
                            ->state(
                                fn(Mission $record): string => (
                                    new MissionProgress($record)->approvedVolunteers() . ' of ' . $record->capacity
                                ),
                            ),
                    ]),
                ]),

            Tabs::make('Mission Details')
                ->tabs([
                    Tab::make('School & Contacts')
                        ->icon('heroicon-o-building-library')
                        ->schema([
                            Grid::make(3)->schema([
                                TextEntry::make('school.total_students')->label('Total Students')->numeric(),
                                TextEntry::make('school.distance')->label('Distance'),
                                TextEntry::make('school.static_duration')->label('Travel Time'),
                            ]),
                            RepeatableEntry::make('school.schoolContacts')
                                ->label('School Contacts')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextEntry::make('name')->label('Contact Name')->weight('medium'),
                                        TextEntry::make('contactType.name')->label('Role')->badge(),
                                        TextEntry::make('phone')
                                            ->label('Phone Number')
                                            ->icon('heroicon-o-phone')
                                            ->url(fn(?string $state): ?string => filled($state)
                                                ? "tel:{$state}"
                                                : null),
                                    ]),
                                ]),
                        ]),

                    Tab::make('Preparation & Guidelines')
                        ->icon('heroicon-o-light-bulb')
                        ->schema([
                            TextEntry::make('mission_prep_notes')
                                ->label('Preparation Notes')
                                ->markdown()
                                ->placeholder('No preparation notes specified.'),
                            TextEntry::make('dressing_recommendations')
                                ->label('Dressing Recommendations')
                                ->placeholder('None'),
                            TextEntry::make('activity_recommendations')
                                ->label('Activity Recommendations')
                                ->placeholder('None'),
                            TextEntry::make('weather_recommendations')->label('Weather Guidance')->placeholder('None'),
                            TextEntry::make('whats_app_link')
                                ->label('WhatsApp Group Link')
                                ->url(fn($state) => $state, true)
                                ->icon('heroicon-o-link')
                                ->placeholder('No WhatsApp group link created.'),
                        ]),

                    Tab::make('Summary & Photos')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            TextEntry::make('executive_summary')
                                ->label('Executive Summary')
                                ->markdown()
                                ->placeholder('Executive summary not generated yet.'),
                            SpatieMediaLibraryImageEntry::make(Mission::MISSION_PHOTOS)
                                ->label('Mission Photos')
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    /**
     * Mission Details Section - Core mission information
     */
    protected static function getMissionDetailsSection(): Section
    {
        return Section::make('Where and what')
            ->columnSpanFull()
            ->description('Pick the school first; its usual times and team size are filled in for you.')
            ->icon('heroicon-o-map-pin')
            ->schema([
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        static::schoolField(),

                        StatusSchema::relationshipSelect(
                            name: 'mission_type_id',
                            label: 'Type of mission',
                            relationship: 'missionType',
                            titleAttribute: 'name',
                            modifyQuery: fn(Builder $query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                            helperText: 'e.g. Sunday Service, Weekend Mission',
                        )
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get, ?Mission $record) {
                                if ($record?->exists || !$state) {
                                    return;
                                }

                                self::applySchoolDefaults($set, $get);
                            })
                            ->placeholder('Choose a type'),
                    ]),
            ])
            ->columns(1)
            ->collapsible();
    }

    /**
     * The school select: search by name, or add a school that isn't in the list yet.
     */
    protected static function schoolField(): Select
    {
        return Select::make('school_id')
            ->label('School')
            ->relationship(
                name: 'school',
                titleAttribute: 'name',
                modifyQueryUsing: fn(Builder $query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
            )
            ->searchable(['name', 'address'])
            ->getOptionLabelFromRecordUsing(fn(School $record): string => $record->address
                ? "{$record->name} · " . str($record->address)->before(',')->limit(30)
                : $record->name)
            ->required()
            ->live()
            ->placeholder('Type the school’s name…')
            ->helperText('Can’t find it? Use the + button to add the school.')
            ->afterStateUpdated(function (?string $state, Set $set, Get $get, ?Mission $record) {
                if ($record?->exists || !$state) {
                    return;
                }

                self::applySchoolDefaults($set, $get);
            })
            ->createOptionModalHeading('Add a school')
            ->createOptionForm(SchoolSchema::quickCreateForm())
            ->createOptionUsing(fn(array $data): int => SchoolSchema::createFromQuickForm(array_filter(
                $data,
                is_string(...),
                ARRAY_FILTER_USE_KEY,
            ))->id)
            ->createOptionAction(fn(Action $action) => $action
                ->slideOver()
                ->modalSubmitActionLabel('Add school')
                ->visible(fn(): bool => userCan(School::permission('create'))));
    }

    /**
     * Team size, theme and term: the last details before saving.
     */
    protected static function getTeamSection(): Section
    {
        return Section::make('Team and theme')
            ->columnSpanFull()
            ->icon('heroicon-o-user-group')
            ->schema([
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('capacity')
                            ->label('How many missioners are needed?')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100)
                            ->placeholder('e.g. 10'),

                        Select::make('school_term_id')
                            ->label('School term')
                            ->relationship(
                                name: 'schoolTerm',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->where(
                                    'is_active',
                                    PRFActiveStatus::ACTIVE,
                                ),
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(
                                fn(): ?int => SchoolTerm::query()
                                    ->where('is_active', PRFActiveStatus::ACTIVE)
                                    ->latest('id')
                                    ->first()
                                    ?->id,
                            )
                            ->helperText('Usually the current term. Add one with + if it’s missing.')
                            ->createOptionForm([
                                TextInput::make('name')->label('Term name')->placeholder('e.g. Term 2')->required(),
                                TextInput::make('year')->label('Year')->numeric()->default(now()->year)->required(),
                            ])
                            ->createOptionUsing(fn(array $data): int => SchoolTerm::query()->create([
                                ...array_filter($data, is_string(...), ARRAY_FILTER_USE_KEY),
                                'is_active' => PRFActiveStatus::ACTIVE,
                            ])->id),
                    ]),

                Textarea::make('theme')
                    ->label('Theme')
                    ->columnSpanFull()
                    ->required()
                    ->rows(2)
                    ->placeholder('e.g. "Walking in Faith"')
                    ->helperText('The main message of the mission. Missioners see it in the app.'),
            ])
            ->collapsible();
    }

    /**
     * Schedule Section - Date and time selection using DateTimeSchema
     */
    protected static function getScheduleSection(): Section
    {
        return DateTimeSchema::make(
            sectionTitle: 'Schedule',
            sectionDescription: 'When will this mission take place? Set the start and end dates/times.',
            sectionIcon: 'heroicon-o-calendar',
            collapsible: true,
            collapsedCallback: fn(?Mission $record) => $record?->exists,
        );
    }

    /**
     * Preparation Section - Pre-mission notes and AI recommendations using AIRecommendationsSchema
     */
    protected static function getPreparationSection(): Section
    {
        return AIRecommendationsSchema::make(
            sectionTitle: 'Preparation Notes',
            sectionDescription: 'Add notes and view AI-generated recommendations to help missionaries prepare',
            sectionIcon: 'heroicon-o-light-bulb',
            collapsible: true,
            includePreparationNotes: true,
            visibleCallback: fn(?Mission $record) => (
                $record?->exists && !$record->status->is(PRFMissionStatus::SERVICED)
            ),
        );
    }

    /**
     * Communication Section - WhatsApp and offline members using ContactSchema
     */
    protected static function getCommunicationSection(): Section
    {
        return ContactSchema::communicationSection(
            sectionTitle: 'Communication',
            sectionDescription: 'Set up communication channels for the mission team',
            sectionIcon: 'heroicon-o-chat-bubble-left-right',
            includeOfflineMembers: true,
            collapsible: true,
            collapsed: true,
        );
    }

    /**
     * Mission Content Section - Executive summary (post-mission)
     */
    protected static function getMissionContentSection(): Section
    {
        return Section::make('Executive Summary')
            ->columnSpanFull()
            ->description('Write a summary of what happened during the mission')
            ->icon('heroicon-o-document-text')
            ->schema([
                MarkdownEditor::make('executive_summary')
                    ->label('Mission Summary')
                    ->columnSpanFull()
                    // ->toolbarButtons([
                    //     'bold',
                    //     'italic',
                    //     'link',
                    //     'bulletList',
                    //     'orderedList',
                    //     'h2',
                    //     'h3',
                    // ])
                    ->placeholder(
                        'Write about what happened during the mission. Include key highlights, challenges faced, and outcomes achieved...',
                    )
                    ->helperText(
                        'This summary will be included in reports and shared with leadership. Use bullet points for key outcomes.',
                    ),
            ])
            ->collapsible();
    }

    /**
     * Media Section - Mission photos using MediaSchema
     */
    protected static function getMediaSection(): Section
    {
        return MediaSchema::make(
            collection: Mission::MISSION_PHOTOS,
            sectionTitle: 'Mission Photos',
            sectionDescription: 'Upload photos from the mission to document the experience',
            sectionIcon: 'heroicon-o-photo',
            label: 'Photos',
            multiple: true,
            maxFiles: 20,
            acceptedFileTypes: ['image/*'],
            collapsible: true,
            collapsed: true,
        );
    }

    /**
     * Auto-fill mission fields from the selected school's defaults for the
     * selected mission type.
     */
    /**
     * Fill in the school's usual times, team size and type. Takes Filament's Set/Get, or plain
     * callables when used outside a form field (e.g. prefilling from the school's page).
     *
     * @param  callable(string, mixed): mixed  $set
     * @param  callable(string): mixed  $get
     */
    public static function applySchoolDefaults(callable $set, callable $get): void
    {
        $schoolId = $get('school_id');

        if (!$schoolId) {
            return;
        }

        $missionTypeId = $get('mission_type_id');

        $service = app(MissionDefaultsService::class);
        $defaults = $service->getDefaultsForSchool(
            is_numeric($schoolId) ? (int) $schoolId : 0,
            is_numeric($missionTypeId) ? (int) $missionTypeId : null,
        );

        if ($defaults['source'] === 'none') {
            return;
        }

        // The form's own 08:00/17:00 are placeholders: the school's usual times replace them,
        // but anything the secretary typed is kept.
        $isUnset = fn(mixed $value, ?string $placeholder = null): bool => (
            blank($value)
            || $placeholder !== null
            && is_string($value)
            && str_starts_with($value, $placeholder)
        );

        $applied = false;

        if ($defaults['start_time'] && $isUnset($get('start_time'), DateTimeSchema::DEFAULT_START_TIME)) {
            $set('start_time', $defaults['start_time']);
            $applied = true;
        }

        if ($defaults['end_time'] && $isUnset($get('end_time'), DateTimeSchema::DEFAULT_END_TIME)) {
            $set('end_time', $defaults['end_time']);
            $applied = true;
        }

        if ($defaults['capacity'] && $isUnset($get('capacity'))) {
            $set('capacity', $defaults['capacity']);
            $applied = true;
        }

        if ($defaults['mission_type_id'] && $isUnset($missionTypeId)) {
            $set('mission_type_id', $defaults['mission_type_id']);
            $applied = true;
        }

        if ($applied && $defaults['source_label']) {
            Notification::make()
                ->title('Filled in from this school’s usual plan')
                ->body($defaults['source_label'])
                ->info()
                ->duration(4000)
                ->send();
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->label('School')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn(Mission $record): ?string => $record->missionType?->name)
                    ->wrap(),
                TextColumn::make('start_date')
                    ->label('When')
                    ->date('D j M Y')
                    ->sortable()
                    ->description(fn(Mission $record): ?string => $record->start_time
                        ? Carbon::parse($record->start_time)->format('g:i A')
                        : null),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(MissionState $state): string => $state->getLabel())
                    ->color(fn(MissionState $state): string => $state->getColor())
                    ->tooltip(fn(Mission $record): ?string => $record->status_reason)
                    ->sortable(),
                TextColumn::make('approved_subscriptions_count')
                    ->label('Team')
                    ->counts([
                        'missionSubscriptions as approved_subscriptions_count' => fn(Builder $query) => $query->where(
                            'status',
                            PRFMissionSubscriptionStatus::APPROVED,
                        ),
                    ])
                    ->formatStateUsing(fn(int $state, Mission $record): string => "{$state} / {$record->capacity}")
                    ->badge()
                    ->color(fn(int $state, Mission $record): string => match (true) {
                        $state >= $record->capacity => 'success',
                        $state >= ($record->capacity / 2) => 'warning',
                        default => 'gray',
                    })
                    ->tooltip('Approved missioners out of those needed')
                    ->sortable(),
                TextColumn::make('schoolTerm.name')->label('Term')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('theme')
                    ->label('Theme')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('teacher_feedback_requested_at')
                    ->label('Feedback asked')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->date('j M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')->multiple()->options(PRFMissionStatus::getOptions())->label('Status'),

                SelectFilter::make('school_term_id')
                    ->label('School Term')
                    ->relationship(
                        name: 'schoolTerm',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                    )
                    ->searchable()
                    ->preload(),
                SelectFilter::make('mission_type_id')
                    ->label('Mission Type')
                    ->relationship(
                        name: 'missionType',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                    )
                    ->searchable()
                    ->preload(),
                Filter::make('start_date')
                    ->schema([
                        DatePicker::make('from')->native(false)->label('From Date'),
                        DatePicker::make('until')->native(false)->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $from = is_string($data['from'] ?? null) ? $data['from'] : null;
                        $until = is_string($data['until'] ?? null) ? $data['until'] : null;

                        return $query->when($from !== null, fn(Builder $query): Builder => $query->whereDate(
                            'start_date',
                            '>=',
                            (string) $from,
                        ))->when($until !== null, fn(Builder $query): Builder => $query->whereDate(
                            'start_date',
                            '<=',
                            (string) $until,
                        ));
                    }),
                Filter::make('funding_source')
                    ->label('Funding Source')
                    ->schema([
                        Select::make('funding_source_filter')
                            ->options([
                                'fellowship_funded' => 'Fellowship-funded',
                                'member_funded' => 'Member-funded',
                            ])
                            ->placeholder('All missions'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['funding_source_filter'] ?? null) {
                            'fellowship_funded' => $query->whereIn(
                                'id',
                                Mission::query()->fellowshipFunded()->select('id'),
                            ),
                            'member_funded' => $query->whereIn('id', Mission::query()->memberFunded()->select('id')),
                            default => $query,
                        };
                    }),
            ])
            ->recordUrl(fn(Mission $record): ?string => (
                userCan(Mission::permission('view')) ? self::getUrl('view', ['record' => $record]) : null
            ))
            ->recordActions([
                MissionActions::approve()->button()->size('sm'),
                ActionGroup::make([
                    Action::make('guided')
                        ->label('Guided view')
                        ->icon('heroicon-m-map')
                        ->url(fn(Mission $record): string => self::getUrl('view', ['record' => $record])),
                    ViewAction::make()
                        ->label('Classic view')
                        ->visible(fn() => userCan(Mission::permission('view'))),
                    EditAction::make()->visible(fn() => userCan(Mission::permission('edit'))),
                    MissionActions::reject(),
                    Action::make('download_report')
                        ->label('Download report')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn(Mission $record): string => URL::temporarySignedRoute(
                            'reports.missions.export',
                            now()->addMinutes(30),
                            ['missionUlid' => $record->ulid],
                        ))
                        ->openUrlInNewTab()
                        ->visible(fn() => userCan(Mission::permission('view'))),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_approve')
                        ->label('Approve selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('Only missions waiting for approval are approved; the rest are skipped.')
                        ->action(
                            /** @param Collection<int, Mission> $records */
                            function (Collection $records): void {
                                $actor = Auth::user();
                                abort_unless($actor instanceof User, 403);

                                $approvable = $records
                                    ->whereInstanceOf(Mission::class)
                                    ->filter(
                                        fn(Mission $mission): bool => $mission->status->canMoveTo(PRFMissionStatus::APPROVED),
                                    );
                                $approvable->each(fn(Mission $mission) => ApproveJob::dispatchSync($mission, $actor));

                                $skipped = $records->count() - $approvable->count();

                                Notification::make()
                                    ->success()
                                    ->title(
                                        "Approved {$approvable->count()} "
                                            . str('mission')->plural($approvable->count()),
                                    )
                                    ->body(
                                        $skipped > 0
                                            ? "{$skipped} skipped because they weren’t waiting for approval."
                                            : null,
                                    )
                                    ->send();
                            },
                        )
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn(): bool => userCan(Mission::permission('edit'))),
                    DeleteBulkAction::make()->visible(fn(): bool => userCan(Mission::permission('delete'))),
                    ForceDeleteBulkAction::make()->visible(fn(): bool => userCan(Mission::permission('forceDelete'))),
                    RestoreBulkAction::make()->visible(fn(): bool => userCan(Mission::permission('restore'))),
                ]),
            ])
            ->defaultSort('start_date', 'desc')
            ->persistSortInSession()
            ->emptyStateHeading('No missions here')
            ->emptyStateDescription('Plan one with “New mission”, or from a school’s page.')
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            MissionProgress::TAB_TEAM => RelationGroup::make('Team', [
                MissionSubscriptionsRelationManager::class,
            ])->icon('heroicon-o-user-group'),

            MissionProgress::TAB_MONEY => RelationGroup::make('Money', [
                RequisitionsRelationManager::class,
                AccountingEventRelationManager::class,
            ])->icon('heroicon-o-banknotes'),

            MissionProgress::TAB_DAY => RelationGroup::make('Mission day', [
                MissionSessionsRelationManager::class,
                WeatherForecastsRelationManager::class,
                SMSLogsRelationManager::class,
            ])->icon('heroicon-o-calendar-days'),

            MissionProgress::TAB_OUTCOMES => RelationGroup::make('Outcomes', [
                SoulsRelationManager::class,
                DebriefNotesRelationManager::class,
                MissionQuestionsRelationManager::class,
            ])->icon('heroicon-o-clipboard-document-check'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMissions::route('/'),
            'create' => CreateMission::route('/create'),
            'view' => ViewMission::route('/{record}'),
            'edit' => EditMission::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['school', 'missionType', 'schoolTerm'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canAccess(): bool
    {
        return userCan(Mission::permission('viewAny'));
    }
}
