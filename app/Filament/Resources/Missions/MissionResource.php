<?php

namespace App\Filament\Resources\Missions;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Filament\Forms\Schemas\AIRecommendationsSchema;
use App\Filament\Forms\Schemas\ContactSchema;
use App\Filament\Forms\Schemas\DateTimeSchema;
use App\Filament\Forms\Schemas\MediaSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\Missions\Pages\CreateMission;
use App\Filament\Resources\Missions\Pages\EditMission;
use App\Filament\Resources\Missions\Pages\ListMissions;
use App\Filament\Resources\Missions\Pages\ViewMission;
use App\Filament\Resources\Missions\RelationManagers\AccountingEventRelationManager;
use App\Filament\Resources\Missions\RelationManagers\DebriefNotesRelationManager;
use App\Filament\Resources\Missions\RelationManagers\MissionQuestionsRelationManager;
use App\Filament\Resources\Missions\RelationManagers\MissionSessionsRelationManager;
use App\Filament\Resources\Missions\RelationManagers\MissionSubscriptionsRelationManager;
use App\Filament\Resources\Missions\RelationManagers\RequisitionsRelationManager;
use App\Filament\Resources\Missions\RelationManagers\SmsLogsRelationManager;
use App\Filament\Resources\Missions\RelationManagers\SoulsRelationManager;
use App\Filament\Resources\Missions\RelationManagers\WeatherForecastsRelationManager;
use App\Jobs\AccountingEvent\EmailFinancialReportJob;
use App\Jobs\AccountingEvent\MakeZeroRequisitionJob;
use App\Jobs\Mission\GenerateExecutiveSummaryJob;
use App\Jobs\Mission\NotifySchoolOfMissionJob;
use App\Jobs\Mission\NotifyWhatsAppGroupJob;
use App\Jobs\Mission\RequestSchoolFeedbackJob;
use App\Jobs\Mission\UploadFilesToDriveJob;
use App\Models\Mission;
use App\Models\School;
use App\Services\MissionDefaultsService;
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
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class MissionResource extends Resource
{
    protected static ?string $model = Mission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|\UnitEnum|null $navigationGroup = 'Missions Secretary';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Mission';

    protected static ?string $pluralModelLabel = 'Missions';

    protected static ?string $navigationLabel = 'Missions';

    protected static ?string $navigationTooltip = 'Manage missionary activities and assignments';

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
        return $record->school->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'School' => $record->school->name,
            'Type' => $record->missionType->name,
            'Start Date' => $record->start_date->format('M j, Y'),
            'Status' => $record->status?->getLabel(),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['theme', 'school.name', 'missionType.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', PRFMissionStatus::PENDING)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $count = static::getNavigationBadge();

        return $count.' pending mission'.($count !== 1 ? 's' : '');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Main Tabs Layout
                Tabs::make('Mission')
                    ->tabs([
                        // Tab 1: Overview (Core mission info - visible on create and edit)
                        Tab::make('Overview')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                static::getMissionDetailsSection(),
                                static::getScheduleSection(),
                            ]),

                        // Tab 2: School (School info - visible on edit)
                        Tab::make('School')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                                static::getSchoolPreviewSection(),
                                static::getSchoolInfoSection(),
                            ])
                            ->visible(fn ($record, Get $get) => $record?->exists || $get('school_id')),

                        // Tab 3: Preparation & Communication
                        Tab::make('Preparation')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->badge(fn ($record) => $record?->exists && ! $record->mission_prep_notes ? '!' : null)
                            ->badgeColor('warning')
                            ->schema([
                                static::getPreparationSection(),
                                static::getCommunicationSection(),
                            ])
                            ->visible(fn ($record) => $record?->exists),

                        // Tab 4: Summary & Media (Post-mission - visible after serviced)
                        Tab::make('Summary & Media')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                static::getMissionContentSection(),
                                static::getMediaSection(),
                            ])
                            ->visible(fn ($record) => $record?->exists && (
                                $record->status === PRFMissionStatus::SERVICED ||
                                $record->status === PRFMissionStatus::POSTPONED
                            )),

                        // Tab 5: Status & Statistics
                        Tab::make('Statistics')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                static::getStatusSection(),
                            ])
                            ->visible(fn ($record) => $record?->exists),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mission Summary')
                    ->columnSpanFull()
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('school.name')
                                    ->label('School')
                                    ->weight('bold')
                                    ->icon('heroicon-o-academic-cap'),
                                TextEntry::make('missionType.name')
                                    ->label('Mission Type')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state?->getLabel())
                                    ->color(fn ($state) => $state?->getColor()),
                                TextEntry::make('schoolTerm.name')
                                    ->label('School Term'),
                                TextEntry::make('theme')
                                    ->label('Theme')
                                    ->columnSpanFull(),
                                TextEntry::make('start_date')
                                    ->label('Start Date')
                                    ->date('M j, Y'),
                                TextEntry::make('end_date')
                                    ->label('End Date')
                                    ->date('M j, Y'),
                                TextEntry::make('capacity')
                                    ->label('Capacity Needed')
                                    ->numeric(),
                                TextEntry::make('mission_subscriptions_count')
                                    ->label('Subscribed Missionaries')
                                    ->state(fn ($record) => $record->missionSubscriptions()->count().' / '.$record->capacity),
                            ]),
                    ]),

                Tabs::make('Mission Details')
                    ->tabs([
                        Tab::make('School & Contacts')
                            ->icon('heroicon-o-building-library')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('school.total_students')
                                            ->label('Total Students')
                                            ->numeric(),
                                        TextEntry::make('school.distance')
                                            ->label('Distance'),
                                        TextEntry::make('school.static_duration')
                                            ->label('Travel Time'),
                                    ]),
                                RepeatableEntry::make('school.schoolContacts')
                                    ->label('School Contacts')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('name')
                                                    ->label('Contact Name')
                                                    ->weight('medium'),
                                                TextEntry::make('contactType.name')
                                                    ->label('Role')
                                                    ->badge(),
                                                TextEntry::make('phone')
                                                    ->label('Phone Number')
                                                    ->icon('heroicon-o-phone')
                                                    ->url(fn ($state) => $state ? "tel:{$state}" : null),
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
                                TextEntry::make('weather_recommendations')
                                    ->label('Weather Guidance')
                                    ->placeholder('None'),
                                TextEntry::make('whats_app_link')
                                    ->label('WhatsApp Group Link')
                                    ->url(fn ($state) => $state, true)
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

    public static function getNotificationActions(): ActionGroup
    {
        return ActionGroup::make([
            Action::make('notify_school')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->label('Notify School')
                ->modalDescription('This will send a notification to the school about this mission.')
                ->action(function ($record) {
                    NotifySchoolOfMissionJob::dispatch($record);
                    Notification::make()
                        ->title('School Notified')
                        ->body('Notification has been queued for delivery.')
                        ->success()
                        ->send();
                }),
            Action::make('request_feedback')
                ->icon('heroicon-o-inbox-arrow-down')
                ->requiresConfirmation()
                ->label('Request Feedback')
                ->modalDescription('This will send a feedback request to the school.')
                ->action(function ($record) {
                    RequestSchoolFeedbackJob::dispatch($record);
                    Notification::make()
                        ->title('Feedback Requested')
                        ->body('Feedback request has been queued for delivery.')
                        ->success()
                        ->send();
                })
                ->visible(fn ($record) => $record && $record->status->value >= PRFMissionStatus::SERVICED->value),
            Action::make('whatsapp_notification')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->requiresConfirmation()
                ->label('WhatsApp Notification')
                ->modalDescription('This will notify members to join the WhatsApp group.')
                ->action(function ($record) {
                    NotifyWhatsAppGroupJob::dispatch($record);
                    Notification::make()
                        ->title('WhatsApp Notification Sent')
                        ->body('Members have been notified to join the group.')
                        ->success()
                        ->send();
                })
                ->visible(fn ($record) => $record && $record->status->value >= PRFMissionStatus::APPROVED->value),
        ])
            ->label('📢 Notifications')
            ->icon('heroicon-o-bell')
            ->color('info')
            ->button();
    }

    public static function getReportActions(): ActionGroup
    {
        return ActionGroup::make([
            Action::make('download_expense_report')
                ->icon('heroicon-o-arrow-down-tray')
                ->label('Download Expense Report')
                ->url(fn ($record) => URL::temporarySignedRoute('reports.mission-expenses.export', now()->addMinutes(30), ['missionUlid' => $record->ulid]))
                ->openUrlInNewTab(),

            Action::make('email_expense_report')
                ->icon('heroicon-o-envelope')
                ->requiresConfirmation()
                ->label('Email Expense Report')
                ->modalDescription('This will email the expense report to the finance team.')
                ->action(function ($record) {
                    EmailFinancialReportJob::dispatch($record->accountingEvent->ulid);
                    Notification::make()
                        ->title('Report Queued')
                        ->body('Expense report will be emailed shortly.')
                        ->success()
                        ->send();
                }),

            Action::make('download_mission_report')
                ->icon('heroicon-o-document-arrow-down')
                ->label('Download Mission Report')
                ->url(fn ($record) => URL::temporarySignedRoute('reports.missions.export', now()->addMinutes(30), ['missionUlid' => $record->ulid]))
                ->openUrlInNewTab(),

            Action::make('make_zero_requisition')
                ->icon('heroicon-o-calculator')
                ->requiresConfirmation()
                ->label('Make Zero Requisition')
                ->modalDescription('This will create a zero-cost requisition for the mission.')
                ->action(function ($record) {
                    MakeZeroRequisitionJob::dispatch($record->accountingEvent);
                    Notification::make()
                        ->title('Zero Requisition Created')
                        ->body('A zero-cost requisition has been created.')
                        ->success()
                        ->send();
                })->visible(fn ($record) => $record?->accountingEvent?->requisitions()->doesntExist()),
        ])
            ->label('📊 Reports')
            ->icon('heroicon-o-document-chart-bar')
            ->color('gray')
            ->button();
    }

    public static function getAIToolsActions(): ActionGroup
    {
        return ActionGroup::make([
            Action::make('generate_summary')
                ->icon('heroicon-o-sparkles')
                ->requiresConfirmation()
                ->label('Generate Executive Summary')
                ->modalDescription('AI will generate an executive summary based on mission data.')
                ->action(function ($record) {
                    GenerateExecutiveSummaryJob::dispatch($record);
                    Notification::make()
                        ->title('Generating Summary')
                        ->body('Executive summary generation has been queued.')
                        ->success()
                        ->send();
                }),
            Action::make('upload_to_drive')
                ->icon('heroicon-o-cloud-arrow-up')
                ->requiresConfirmation()
                ->label('Upload Media to Drive')
                ->modalDescription('This will upload all mission photos to Google Drive.')
                ->action(function ($record) {
                    UploadFilesToDriveJob::dispatch($record->id);
                    Notification::make()
                        ->title('Upload Started')
                        ->body('Media files are being uploaded to Drive.')
                        ->success()
                        ->send();
                }),
        ])
            ->label('🤖 AI & Tools')
            ->icon('heroicon-o-cog-6-tooth')
            ->color('warning')
            ->button();
    }

    /**
     * Mission Details Section - Core mission information
     */
    protected static function getMissionDetailsSection(): Section
    {
        return Section::make('Mission Details')
            ->columnSpanFull()
            ->description('Enter the basic information about this mission')
            ->icon('heroicon-o-map-pin')
            ->schema([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        StatusSchema::relationshipSelect(
                            name: 'school_term_id',
                            label: 'School Term',
                            relationship: 'schoolTerm',
                            titleAttribute: 'name',
                            helperText: 'Select which school term this mission takes place in',
                            modifyQuery: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                        )->placeholder('Choose a term (e.g., Term 1 2024)'),

                        StatusSchema::relationshipSelect(
                            name: 'mission_type_id',
                            label: 'Mission Type',
                            relationship: 'missionType',
                            titleAttribute: 'name',
                            modifyQuery: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                            helperText: 'What kind of mission is this? Defaults are applied per mission type.',
                        )
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get, $record) {
                                if ($record?->exists || ! $state) {
                                    return;
                                }

                                // Choosing a mission type applies that type's
                                // typical times and team size for this school
                                self::applySchoolDefaults($set, $get, overwrite: true);
                            })
                            ->placeholder('Choose type (e.g., School Visit, Outreach)'),

                        StatusSchema::enumSelect(
                            name: 'status',
                            label: 'Status',
                            enumClass: PRFMissionStatus::class,
                            default: PRFMissionStatus::PENDING->value,
                            helperText: 'Current status of this mission',
                        )
                            ->live()
                            ->disableOptionWhen(function (string $value, $record): bool {
                                if (intval($value) !== PRFMissionStatus::SERVICED->value) {
                                    return false;
                                }

                                return $record?->exists && $record->status !== PRFMissionStatus::SERVICED;
                            })
                            ->hint(fn ($record) => $record?->exists && $record->status !== PRFMissionStatus::SERVICED
                                ? 'Use "Complete Mission" button to mark as serviced'
                                : null),
                    ]),

                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        StatusSchema::relationshipSelect(
                            name: 'school_id',
                            label: 'School',
                            relationship: 'school',
                            titleAttribute: 'name',
                            modifyQuery: fn ($query) => $query
                                ->where('is_active', PRFActiveStatus::ACTIVE)
                                ->with(['schoolContacts', 'schoolContacts.contactType']),
                            helperText: 'Select the school where this mission will take place',
                        )
                            ->live()
                            ->placeholder('Start typing to search for a school...')
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get, $record) {
                                if ($record?->exists || ! $state) {
                                    return;
                                }

                                self::applySchoolDefaults($set, $get);
                            }),

                        TextInput::make('capacity')
                            ->label('Missionaries Needed')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100)
                            ->placeholder('e.g., 10')
                            ->helperText('How many missionaries are required for this mission?'),
                    ]),

                Textarea::make('theme')
                    ->label('Theme')
                    ->columnSpanFull()
                    ->required()
                    ->rows(2)
                    ->placeholder('e.g., "Walking in Faith" or "The Love of Christ"')
                    ->helperText('Enter the main topic or message for this mission. This will be shared with missionaries.'),

                TextInput::make('ulid')
                    ->label('Unique ID')
                    ->visible(app()->isLocal())
                    ->disabled()
                    ->columnSpanFull()
                    ->helperText('System-generated unique identifier (for technical reference only)'),
            ])
            ->columns(1)
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
            collapsedCallback: fn ($record) => $record?->exists,
        );
    }

    /**
     * School Preview Section - For creation (before record exists)
     */
    protected static function getSchoolPreviewSection(): Section
    {
        return Section::make('Selected School')
            ->columnSpanFull()
            ->description('Preview of the school you selected')
            ->icon('heroicon-o-eye')
            ->schema([
                Placeholder::make('selected_school_info')
                    ->label('')
                    ->content(function (Get $get) {
                        $schoolId = $get('school_id');
                        if (! $schoolId) {
                            return new HtmlString('<p class="text-gray-500">Select a school from the Overview tab to see its details here.</p>');
                        }

                        $school = School::with(['schoolContacts', 'schoolContacts.contactType'])
                            ->find($schoolId);

                        if (! $school) {
                            return new HtmlString('<p class="text-gray-500">School information is not available. Please try selecting again.</p>');
                        }

                        return static::buildSchoolInfoHtml($school);
                    })
                    ->columnSpanFull(),
            ])
            ->visible(fn (Get $get, $record) => ! $record?->exists && $get('school_id'))
            ->collapsible();
    }

    /**
     * School Info Section - For existing records
     */
    protected static function getSchoolInfoSection(): Section
    {
        return Section::make('School Information')
            ->columnSpanFull()
            ->description('Details about the school for this mission')
            ->icon('heroicon-o-building-library')
            ->schema([
                Grid::make(4)
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('school_name')
                            ->label('School Name')
                            ->content(fn ($record) => $record?->school?->name ?? 'No school selected'),

                        Placeholder::make('school_student_count')
                            ->label('Total Students')
                            ->content(fn ($record) => $record?->school?->total_students
                                ? number_format($record->school->total_students).' students'
                                : 'Not specified'),

                        Placeholder::make('school_distance')
                            ->label('Distance')
                            ->content(fn ($record) => $record?->school?->distance ?? 'Not specified'),

                        Placeholder::make('school_travel_time')
                            ->label('Travel Time')
                            ->content(fn ($record) => $record?->school?->static_duration ?? 'Not specified'),
                    ]),

                Placeholder::make('school_contacts_display')
                    ->label('School Contacts')
                    ->content(function ($record) {
                        if (! $record?->school?->schoolContacts || $record->school->schoolContacts->count() === 0) {
                            return new HtmlString('<p class="text-gray-500">No contact information available for this school.</p>');
                        }

                        return static::buildContactsHtml($record->school->schoolContacts);
                    })
                    ->columnSpanFull()
                    ->helperText('These are the school contacts you can reach out to for coordination.'),
            ])
            ->visible(fn ($record) => $record?->exists && $record?->school_id)
            ->collapsible();
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
            visibleCallback: fn ($record) => $record?->exists && $record->status !== PRFMissionStatus::SERVICED,
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
                    ->placeholder('Write about what happened during the mission. Include key highlights, challenges faced, and outcomes achieved...')
                    ->helperText('This summary will be included in reports and shared with leadership. Use bullet points for key outcomes.'),
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
     * Status Section - Mission statistics using StatusSchema helpers
     */
    protected static function getStatusSection(): Section
    {
        return Section::make('Mission Statistics')
            ->columnSpanFull()
            ->description('View subscription progress and mission status at a glance')
            ->icon('heroicon-o-chart-pie')
            ->schema([
                Placeholder::make('mission_stats')
                    ->label('')
                    ->content(function ($record) {
                        if (! $record) {
                            return new HtmlString('<p class="text-gray-500">Statistics will appear here once the mission is created.</p>');
                        }

                        $subscribed = $record->missionSubscriptions()->count();
                        $approved = $record->missionSubscriptions()
                            ->where('status', PRFMissionSubscriptionStatus::APPROVED)
                            ->count();
                        $needed = max(0, $record->capacity - $approved);
                        $percentage = $record->capacity > 0 ? round(($approved / $record->capacity) * 100, 1) : 0;

                        // Build stats cards using StatusSchema helper
                        $statsHtml = StatusSchema::buildStatsCards([
                            [
                                'value' => $subscribed,
                                'label' => 'Total Subscribed',
                                'icon' => '',
                            ],
                            [
                                'value' => "{$approved} / {$record->capacity}",
                                'label' => 'Approved Missionaries',
                                'icon' => '',
                            ],
                            [
                                'value' => $needed,
                                'label' => 'Still Needed',
                                'icon' => '',
                            ],
                        ]);

                        // Build progress bar using StatusSchema helper
                        $progressBar = StatusSchema::buildProgressBar(
                            percentage: $percentage,
                            label: "{$percentage}% of missionary capacity filled",
                        );

                        return new HtmlString("
                            <div class='space-y-4'>
                                {$statsHtml->toHtml()}
                                <div class='mt-4'>
                                    {$progressBar->toHtml()}
                                </div>
                            </div>
                        ");
                    })
                    ->columnSpanFull()
                    ->helperText('This shows how many missionaries have signed up compared to how many are needed.'),

                Toggle::make('teacher_feedback_requested_at')
                    ->label('Teacher Feedback Requested')
                    ->helperText('This checkbox shows whether feedback has been requested from the school. Use the action button in the header to request feedback.')
                    ->disabled(true),
            ])
            ->collapsible();
    }

    /**
     * Build school info HTML for preview
     */
    protected static function buildSchoolInfoHtml(School $school): HtmlString
    {
        $html = '<div class="space-y-3">';
        $html .= '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">';
        $html .= '<div><strong>Name:</strong><br>'.e($school->name).'</div>';

        if ($school->total_students) {
            $html .= '<div><strong>Students:</strong><br>'.number_format($school->total_students).'</div>';
        }
        if ($school->distance) {
            $html .= '<div><strong>Distance:</strong><br>'.($school->distance).'</div>';
        }
        if ($school->static_duration) {
            $html .= '<div><strong>Travel Time:</strong><br>'.($school->static_duration).'</div>';
        }
        $html .= '</div>';

        if ($school->schoolContacts->count() > 0) {
            $html .= '<hr class="my-4">';
            $html .= '<div><strong>Contacts:</strong></div>';
            $html .= static::buildContactsHtml($school->schoolContacts)->toHtml();
        }
        $html .= '</div>';

        return new HtmlString($html);
    }

    /**
     * Build contacts HTML display
     */
    protected static function buildContactsHtml($contacts): HtmlString
    {
        $html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">';
        foreach ($contacts as $contact) {
            $html .= '<div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border">';
            $html .= '<div class="font-semibold">'.e($contact->preferred_name ?? $contact->name).'</div>';
            $html .= '<div class="text-sm text-gray-500">'.e($contact->contactType?->name ?? 'Unknown').'</div>';
            if ($contact->phone) {
                $html .= '<div class="mt-1"><a href="tel:'.e($contact->phone).'" class="text-primary-600 hover:text-primary-500 text-sm">'.e($contact->phone).'</a></div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        return new HtmlString($html);
    }

    /**
     * Auto-fill mission fields from the selected school's defaults for the
     * selected mission type.
     */
    public static function applySchoolDefaults(Set $set, Get $get, bool $overwrite = false): void
    {
        $schoolId = $get('school_id');

        if (! $schoolId) {
            return;
        }

        $missionTypeId = $get('mission_type_id');

        $service = app(MissionDefaultsService::class);
        $defaults = $service->getDefaultsForSchool(
            $schoolId,
            $missionTypeId ? (int) $missionTypeId : null,
        );

        if ($defaults['source'] === 'none') {
            return;
        }

        if ($defaults['start_time'] && ($overwrite || ! $get('start_time'))) {
            $set('start_time', $defaults['start_time']);
        }

        if ($defaults['end_time'] && ($overwrite || ! $get('end_time'))) {
            $set('end_time', $defaults['end_time']);
        }

        if ($defaults['capacity'] && ($overwrite || ! $get('capacity'))) {
            $set('capacity', $defaults['capacity']);
        }

        if (! $overwrite && $defaults['mission_type_id'] && ! $get('mission_type_id')) {
            $set('mission_type_id', $defaults['mission_type_id']);
        }

        if ($defaults['source_label']) {
            Notification::make()
                ->title('Auto-fill Applied')
                ->body($defaults['source_label'])
                ->info()
                ->duration(3000)
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
                    ->wrap()
                    ->tooltip(fn ($record) => $record->school->name.' - '.$record->theme),
                TextColumn::make('missionType.name')
                    ->label('Type')
                    ->wrap()
                    ->badge()
                    ->color('info'),
                TextColumn::make('schoolTerm.name')
                    ->label('Term')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->timezone(Auth::user()->timezone)
                    ->description(fn ($record) => $record->start_time ? 'at '.Carbon::parse($record->start_time)->format('g:i A') : null),
                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($record) => $record->status?->getLabel())
                    ->badge()
                    ->color(fn ($record) => $record->status?->getColor())
                    ->sortable(),
                TextColumn::make('mission_subscriptions_count')
                    ->label('Subscriptions')
                    ->counts('missionSubscriptions')
                    ->badge()
                    ->color(function ($record) {
                        $count = $record->mission_subscriptions_count ?? 0;
                        $capacity = $record->capacity ?? 1;
                        $percentage = ($count / $capacity) * 100;

                        return match (true) {
                            $percentage >= 100 => 'success',
                            $percentage >= 80 => 'warning',
                            $percentage >= 50 => 'info',
                            default => 'gray',
                        };
                    })
                    ->description(fn ($record) => "of {$record->capacity} needed")
                    ->sortable(),
                TextColumn::make('theme')
                    ->label('Theme')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('teacher_feedback_requested_at')
                    ->label('Feedback Requested')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->multiple()
                    ->options(PRFMissionStatus::getOptions())
                    ->default([
                        PRFMissionStatus::PENDING->value,
                        PRFMissionStatus::APPROVED->value,
                        PRFMissionStatus::FULLY_SUBSCRIBED->value,
                    ])
                    ->label('Status'),

                SelectFilter::make('school_term_id')
                    ->label('School Term')
                    ->relationship(
                        name: 'schoolTerm',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                    )
                    ->searchable()
                    ->preload(),
                SelectFilter::make('mission_type_id')
                    ->label('Mission Type')
                    ->relationship(
                        name: 'missionType',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                    )
                    ->searchable()
                    ->preload(),
                Filter::make('start_date')
                    ->schema([
                        DatePicker::make('from')
                            ->native(false)
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->native(false)
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
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
                            'fellowship_funded' => $query->fellowshipFunded(),
                            'member_funded' => $query->memberFunded(),
                            default => $query,
                        };
                    }),
                Filter::make('capacity_status')
                    ->label('Subscription Status')
                    ->schema([
                        Select::make('capacity_filter')
                            ->options([
                                'under_subscribed' => 'Under-subscribed',
                                'fully_subscribed' => 'Fully subscribed',
                                'over_subscribed' => 'Over-subscribed',
                            ])
                            ->placeholder('All missions'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['capacity_filter']) {
                            return $query;
                        }

                        return $query->withCount(['missionSubscriptions as approved_subscriptions_count' => function ($query) {
                            $query->where('status', PRFMissionSubscriptionStatus::APPROVED);
                        }])
                            ->having('approved_subscriptions_count', match ($data['capacity_filter']) {
                                'under_subscribed' => '<',
                                'fully_subscribed' => '=',
                                'over_subscribed' => '>',
                            }, DB::raw('capacity'));
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->visible(fn () => userCan('view mission')),
                    EditAction::make()
                        ->visible(fn () => userCan('edit mission')),
                    Action::make('download_report')
                        ->label('Download Report')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->url(fn ($record) => URL::temporarySignedRoute('reports.missions.export', now()->addMinutes(30), ['missionUlid' => $record->ulid]))
                        ->openUrlInNewTab()
                        ->visible(fn () => userCan('view mission')),
                ])
                    ->tooltip('Actions'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $updated = 0;
                            foreach ($records as $record) {
                                if ($record->status === PRFMissionStatus::PENDING) {
                                    $record->update(['status' => PRFMissionStatus::APPROVED]);
                                    $updated++;
                                }
                            }

                            if ($updated > 0) {
                                Notification::make()
                                    ->title('Missions Approved')
                                    ->body("{$updated} mission(s) have been approved successfully.")
                                    ->success()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Approve Selected Missions')
                        ->modalDescription('Only pending missions will be approved. Are you sure?')
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('bulk_reject')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $updated = 0;
                            foreach ($records as $record) {
                                if ($record->status === PRFMissionStatus::PENDING) {
                                    $record->update(['status' => PRFMissionStatus::REJECTED]);
                                    $updated++;
                                }
                            }

                            if ($updated > 0) {
                                Notification::make()
                                    ->title('Missions Rejected')
                                    ->body("{$updated} mission(s) have been rejected.")
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Reject Selected Missions')
                        ->modalDescription('Only pending missions will be rejected. Are you sure?')
                        ->deselectRecordsAfterCompletion(),
                ])->visible(fn () => userCan('delete mission')),
            ])
            ->defaultSort('start_date', 'asc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            // Team & Planning Group
            RelationGroup::make('Team & Planning', [
                MissionSubscriptionsRelationManager::class,
            ])
                ->icon('heroicon-o-user-group'),

            // Finance Group
            RelationGroup::make('Finance', [
                RequisitionsRelationManager::class,
                AccountingEventRelationManager::class,
            ])
                ->icon('heroicon-o-currency-dollar'),

            // Execution Group
            RelationGroup::make('Execution', [
                MissionSessionsRelationManager::class,
                WeatherForecastsRelationManager::class,
                SmsLogsRelationManager::class,
            ])
                ->icon('heroicon-o-play-circle'),

            // Outcomes Group
            RelationGroup::make('Outcomes', [
                SoulsRelationManager::class,
                DebriefNotesRelationManager::class,
                MissionQuestionsRelationManager::class,
            ])
                ->icon('heroicon-o-clipboard-document-check'),
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

    public static function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Mission')
                ->icon('heroicon-o-plus')
                ->visible(fn () => userCan('create mission')),
            Action::make('export_missions')
                ->label('Export Missions')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    // This would trigger an export job
                    return response()->download(storage_path('app/exports/missions.xlsx'));
                })
                ->visible(fn () => userCan('view mission')),
        ];
    }

    public static function getDefaultEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'school',
                'school.schoolContacts',
                'school.schoolContacts.contactType',
                'missionType',
                'schoolTerm',
            ])
            ->withCount('missionSubscriptions');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('start_date', 'asc')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canAccess(): bool
    {
        return userCan('viewAny mission');
    }
}
