<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Filament\Resources\MissionResource\Pages;
use App\Filament\Resources\MissionResource\RelationManagers;
use App\Models\Mission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class MissionResource extends Resource
{
    protected static ?string $model = Mission::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Missions Secretary';

    protected static ?int $navigationSort = 1;

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
            'Status' => PRFMissionStatus::fromValue($record->status)->getLabel(),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['theme', 'school.name', 'missionType.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', PRFMissionStatus::PENDING->value)->count();
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Main Tabs Layout
                Forms\Components\Tabs::make('Mission')
                    ->tabs([
                        // Tab 1: Overview (Core mission info - visible on create and edit)
                        Forms\Components\Tabs\Tab::make('Overview')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                static::getMissionDetailsSection(),
                                static::getScheduleSection(),
                            ]),

                        // Tab 2: School (School info - visible on edit)
                        Forms\Components\Tabs\Tab::make('School')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                                static::getSchoolPreviewSection(),
                                static::getSchoolInfoSection(),
                            ])
                            ->visible(fn ($record, Forms\Get $get) => $record?->exists || $get('school_id')),

                        // Tab 3: Preparation & Communication
                        Forms\Components\Tabs\Tab::make('Preparation')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->badge(fn ($record) => $record?->exists && ! $record->mission_prep_notes ? '!' : null)
                            ->badgeColor('warning')
                            ->schema([
                                static::getPreparationSection(),
                                static::getCommunicationSection(),
                            ])
                            ->visible(fn ($record) => $record?->exists),

                        // Tab 4: Summary & Media (Post-mission - visible after serviced)
                        Forms\Components\Tabs\Tab::make('Summary & Media')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                static::getMissionContentSection(),
                                static::getMediaSection(),
                            ])
                            ->visible(fn ($record) => $record?->exists && (
                                intval($record->status) === PRFMissionStatus::SERVICED->value ||
                                intval($record->status) === PRFMissionStatus::POSTPONED->value
                            )),

                        // Tab 5: Status & Statistics
                        Forms\Components\Tabs\Tab::make('Statistics')
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

    /**
     * Mission Details Section - Core mission information
     */
    protected static function getMissionDetailsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Mission Details')
            ->description('Core mission information')
            ->icon('heroicon-o-map-pin')
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Select::make('school_term_id')
                            ->label('📅 School Term')
                            ->required()
                            ->relationship('schoolTerm', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\Select::make('mission_type_id')
                            ->label('📋 Mission Type')
                            ->required()
                            ->relationship(
                                name: 'missionType',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                            )
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->label('📊 Status')
                            ->required()
                            ->options(PRFMissionStatus::getOptions())
                            ->default(PRFMissionStatus::PENDING->value)
                            ->hiddenOn(['create'])
                            ->live()
                            ->native(false),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('school_id')
                            ->label('🏫 School')
                            ->required()
                            ->relationship(
                                name: 'school',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE)
                                    ->with(['schoolContacts', 'schoolContacts.contactType']),
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->native(false)
                            ->helperText('Select the school for this mission'),
                        Forms\Components\TextInput::make('capacity')
                            ->label('👥 Missionaries Needed')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Number of missionaries required'),
                    ]),
                Forms\Components\Textarea::make('theme')
                    ->label('📖 Theme')
                    ->columnSpanFull()
                    ->required()
                    ->rows(2)
                    ->placeholder('Enter the mission theme or topic...'),
                Forms\Components\TextInput::make('ulid')
                    ->label('ULID')
                    ->visible(app()->isLocal())
                    ->disabled()
                    ->columnSpanFull(),
            ])
            ->columns(1)
            ->collapsible();
    }

    /**
     * Schedule Section - Date and time selection
     */
    protected static function getScheduleSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Schedule')
            ->description('Mission date and time')
            ->icon('heroicon-o-calendar')
            ->schema([
                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->timezone(Auth::user()->timezone)
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state && ! $get('end_date')) {
                                    $set('end_date', $state);
                                }
                            }),
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Start Time')
                            ->seconds(false)
                            ->native(false)
                            ->required()
                            ->default('08:00')
                            ->format('H:i'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->timezone(Auth::user()->timezone)
                            ->native(false)
                            ->afterOrEqual('start_date'),
                        Forms\Components\TimePicker::make('end_time')
                            ->label('End Time')
                            ->seconds(false)
                            ->required()
                            ->native(false)
                            ->default('17:00')
                            ->format('H:i'),
                    ]),
            ])
            ->collapsible()
            ->collapsed(fn ($record) => $record?->exists);
    }

    /**
     * School Preview Section - For creation (before record exists)
     */
    protected static function getSchoolPreviewSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Selected School')
            ->description('Preview of selected school information')
            ->icon('heroicon-o-eye')
            ->schema([
                Forms\Components\Placeholder::make('selected_school_info')
                    ->label('')
                    ->content(function (Forms\Get $get) {
                        $schoolId = $get('school_id');
                        if (! $schoolId) {
                            return 'Select a school to view its information';
                        }

                        $school = \App\Models\School::with(['schoolContacts', 'schoolContacts.contactType'])
                            ->find($schoolId);

                        if (! $school) {
                            return 'School information not available';
                        }

                        return static::buildSchoolInfoHtml($school);
                    })
                    ->columnSpanFull(),
            ])
            ->visible(fn (Forms\Get $get, $record) => ! $record?->exists && $get('school_id'))
            ->collapsible();
    }

    /**
     * School Info Section - For existing records
     */
    protected static function getSchoolInfoSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('School Information')
            ->description('Details about the mission school')
            ->icon('heroicon-o-building-library')
            ->schema([
                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\Placeholder::make('school_name')
                            ->label('🏫 School Name')
                            ->content(fn ($record) => $record?->school?->name ?? 'No school selected'),
                        Forms\Components\Placeholder::make('school_student_count')
                            ->label('👥 Total Students')
                            ->content(fn ($record) => $record?->school?->total_students ? number_format($record->school->total_students) : 'Not specified'),
                        Forms\Components\Placeholder::make('school_distance')
                            ->label('📍 Distance')
                            ->content(fn ($record) => $record?->school?->distance ?? 'Not specified'),
                        Forms\Components\Placeholder::make('school_travel_time')
                            ->label('⏱️ Travel Time')
                            ->content(fn ($record) => $record?->school?->static_duration ?? 'Not specified'),
                    ]),
                Forms\Components\Placeholder::make('school_contacts_display')
                    ->label('📞 School Contacts')
                    ->content(function ($record) {
                        if (! $record?->school?->schoolContacts || $record->school->schoolContacts->count() === 0) {
                            return 'No contacts available for this school';
                        }

                        return static::buildContactsHtml($record->school->schoolContacts);
                    })
                    ->columnSpanFull(),
            ])
            ->visible(fn ($record) => $record?->exists && $record?->school_id)
            ->collapsible()
            ->collapsed();
    }

    /**
     * Preparation Section - Pre-mission notes and AI recommendations
     */
    protected static function getPreparationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Preparation Notes')
            ->description('Mission preparation details and AI recommendations')
            ->icon('heroicon-o-light-bulb')
            ->schema([
                Forms\Components\Textarea::make('mission_prep_notes')
                    ->label('📝 Preparation Notes')
                    ->columnSpanFull()
                    ->rows(3)
                    ->placeholder('Enter preparation notes for the mission...'),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Textarea::make('dressing_recommendations')
                            ->label('👔 Dressing Recommendations')
                            ->hint('Generated by AI based on weather forecast')
                            ->rows(3)
                            ->placeholder('Dressing recommendations will appear here...'),
                        Forms\Components\Textarea::make('activity_recommendations')
                            ->label('🎯 Activity Recommendations')
                            ->hint('Generated by AI based on weather forecast')
                            ->rows(3)
                            ->placeholder('Activity recommendations will appear here...'),
                    ]),
            ])
            ->visible(fn ($record) => $record?->exists && intval($record->status) !== PRFMissionStatus::SERVICED->value)
            ->collapsible();
    }

    /**
     * Communication Section - WhatsApp and offline members
     */
    protected static function getCommunicationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Communication')
            ->description('Contact information and group links')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->schema([
                Forms\Components\TextInput::make('whats_app_link')
                    ->label('💬 WhatsApp Group Link')
                    ->columnSpanFull()
                    ->url()
                    ->placeholder('https://chat.whatsapp.com/XXXXXXXXXX')
                    ->hint('Link to the WhatsApp group for this mission'),
                Forms\Components\Repeater::make('offline_members')
                    ->label('📱 Offline Members')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required(),
                                PhoneInput::make('phone_number')
                                    ->required(),
                            ]),
                    ])
                    ->addActionLabel('Add offline member')
                    ->collapsible()
                    ->collapsed()
                    ->defaultItems(0)
                    ->columnSpanFull(),
            ])
            ->collapsible()
            ->collapsed();
    }

    /**
     * Mission Content Section - Executive summary (post-mission)
     */
    protected static function getMissionContentSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Executive Summary')
            ->description('Mission summary and outcomes')
            ->icon('heroicon-o-document-text')
            ->schema([
                Forms\Components\MarkdownEditor::make('executive_summary')
                    ->label('')
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'link',
                        'bulletList',
                        'orderedList',
                        'h2',
                        'h3',
                    ])
                    ->placeholder('Write the executive summary of the mission...')
                    ->hint('This will be visible after the mission is completed'),
            ])
            ->collapsible();
    }

    /**
     * Media Section - Mission photos
     */
    protected static function getMediaSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Mission Photos')
            ->description('Upload photos from the mission')
            ->icon('heroicon-o-photo')
            ->schema([
                Forms\Components\SpatieMediaLibraryFileUpload::make(Mission::MISSION_PHOTOS)
                    ->label('')
                    ->multiple()
                    ->columnSpanFull()
                    ->collection(Mission::MISSION_PHOTOS)
                    ->disk(config('filament.default_filesystem_disk'))
                    ->acceptedFileTypes(['image/*'])
                    ->maxFiles(20)
                    ->hint('Upload photos from the mission. Maximum 20 files.'),
            ])
            ->collapsible()
            ->collapsed();
    }

    /**
     * Status Section - Mission statistics
     */
    protected static function getStatusSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Mission Statistics')
            ->description('Subscription and status information')
            ->icon('heroicon-o-chart-pie')
            ->schema([
                Forms\Components\Placeholder::make('mission_stats')
                    ->label('')
                    ->content(function ($record) {
                        if (! $record) {
                            return 'No data available';
                        }

                        $subscribed = $record->missionSubscriptions()->count();
                        $approved = $record->missionSubscriptions()
                            ->where('status', PRFMissionSubscriptionStatus::APPROVED->value)
                            ->count();
                        $needed = max(0, $record->capacity - $approved);
                        $percentage = $record->capacity > 0 ? round(($approved / $record->capacity) * 100, 1) : 0;

                        $statusEmoji = match (true) {
                            $percentage >= 100 => '🟢',
                            $percentage >= 80 => '🟡',
                            $percentage >= 50 => '🔵',
                            default => '⚪',
                        };

                        $progressBar = static::buildProgressBar($percentage);

                        return new \Illuminate\Support\HtmlString("
                            <div class='space-y-4'>
                                <div class='grid grid-cols-3 gap-4'>
                                    <div class='p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center'>
                                        <div class='text-2xl font-bold'>{$subscribed}</div>
                                        <div class='text-sm text-gray-500'>Total Subscribed</div>
                                    </div>
                                    <div class='p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center'>
                                        <div class='text-2xl font-bold'>{$approved} / {$record->capacity}</div>
                                        <div class='text-sm text-gray-500'>Approved {$statusEmoji}</div>
                                    </div>
                                    <div class='p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center'>
                                        <div class='text-2xl font-bold'>{$needed}</div>
                                        <div class='text-sm text-gray-500'>Still Needed</div>
                                    </div>
                                </div>
                                {$progressBar}
                            </div>
                        ");
                    })
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('teacher_feedback_requested_at')
                    ->label('✅ Teacher Feedback Requested')
                    ->hint('Request teacher feedback using the action button in the header.')
                    ->disabled(true),
            ])
            ->collapsible();
    }

    /**
     * Build progress bar HTML
     */
    protected static function buildProgressBar(float $percentage): string
    {
        $color = match (true) {
            $percentage >= 100 => 'bg-green-500',
            $percentage >= 80 => 'bg-yellow-500',
            $percentage >= 50 => 'bg-blue-500',
            default => 'bg-gray-400',
        };

        $width = min($percentage, 100);

        return "
            <div class='w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4'>
                <div class='{$color} h-4 rounded-full transition-all duration-300' style='width: {$width}%'></div>
            </div>
            <div class='text-center text-sm text-gray-500'>{$percentage}% capacity filled</div>
        ";
    }

    /**
     * Build school info HTML
     */
    protected static function buildSchoolInfoHtml(\App\Models\School $school): \Illuminate\Support\HtmlString
    {
        $html = '<div class="space-y-3">';
        $html .= '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">';
        $html .= '<div><strong>🏫 Name:</strong><br>'.e($school->name).'</div>';

        if ($school->total_students) {
            $html .= '<div><strong>👥 Students:</strong><br>'.number_format($school->total_students).'</div>';
        }
        if ($school->distance) {
            $html .= '<div><strong>📍 Distance:</strong><br>'.($school->distance).'</div>';
        }
        if ($school->static_duration) {
            $html .= '<div><strong>⏱️ Travel Time:</strong><br>'.($school->static_duration).'</div>';
        }
        $html .= '</div>';

        if ($school->schoolContacts->count() > 0) {
            $html .= '<hr class="my-4">';
            $html .= '<div><strong>📞 Contacts:</strong></div>';
            $html .= static::buildContactsHtml($school->schoolContacts)->toHtml();
        }
        $html .= '</div>';

        return new \Illuminate\Support\HtmlString($html);
    }

    /**
     * Build contacts HTML
     */
    protected static function buildContactsHtml($contacts): \Illuminate\Support\HtmlString
    {
        $html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">';
        foreach ($contacts as $contact) {
            $html .= '<div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border">';
            $html .= '<div class="font-semibold">'.e($contact->preferred_name ?? $contact->name).'</div>';
            $html .= '<div class="text-sm text-gray-500">'.e($contact->contactType?->name ?? 'Unknown').'</div>';
            if ($contact->phone) {
                $html .= '<div class="mt-1"><a href="tel:'.e($contact->phone).'" class="text-primary-600 hover:text-primary-500 text-sm">📞 '.e($contact->phone).'</a></div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        return new \Illuminate\Support\HtmlString($html);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('school.name')
                    ->label('School')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->tooltip(fn ($record) => $record->school->name.' - '.$record->theme),
                Tables\Columns\TextColumn::make('missionType.name')
                    ->label('Type')
                    ->wrap()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('schoolTerm.name')
                    ->label('Term')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->timezone(Auth::user()->timezone)
                    ->description(fn ($record) => $record->start_time ? 'at '.\Carbon\Carbon::parse($record->start_time)->format('g:i A') : null),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($record) => PRFMissionStatus::fromValue($record->status)->getLabel())
                    ->badge()
                    ->color(fn ($record) => PRFMissionStatus::fromValue($record->status)->getColor())
                    ->sortable(),
                Tables\Columns\TextColumn::make('mission_subscriptions_count')
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
                Tables\Columns\TextColumn::make('theme')
                    ->label('Theme')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('teacher_feedback_requested_at')
                    ->label('Feedback Requested')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options(PRFMissionStatus::getOptions())
                    ->default([
                        PRFMissionStatus::PENDING->value,
                        PRFMissionStatus::APPROVED->value,
                        PRFMissionStatus::FULLY_SUBSCRIBED->value,
                    ])
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('school_term_id')
                    ->label('School Term')
                    ->relationship(
                        name: 'schoolTerm',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                    )
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('mission_type_id')
                    ->label('Mission Type')
                    ->relationship(
                        name: 'missionType',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                    )
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('start_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->native(false)
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
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
                Tables\Filters\Filter::make('capacity_status')
                    ->label('Subscription Status')
                    ->form([
                        Forms\Components\Select::make('capacity_filter')
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
                            $query->where('status', PRFMissionSubscriptionStatus::APPROVED->value);
                        }])
                            ->having('approved_subscriptions_count', match ($data['capacity_filter']) {
                                'under_subscribed' => '<',
                                'fully_subscribed' => '=',
                                'over_subscribed' => '>',
                            }, DB::raw('capacity'));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->visible(fn () => userCan('view mission')),
                    Tables\Actions\EditAction::make()
                        ->visible(fn () => userCan('edit mission')),

                ])
                    ->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $updated = 0;
                            foreach ($records as $record) {
                                if ($record->status === PRFMissionStatus::PENDING->value) {
                                    $record->update(['status' => PRFMissionStatus::APPROVED->value]);
                                    $updated++;
                                }
                            }

                            if ($updated > 0) {
                                \Filament\Notifications\Notification::make()
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
                    Tables\Actions\BulkAction::make('bulk_reject')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $updated = 0;
                            foreach ($records as $record) {
                                if ($record->status === PRFMissionStatus::PENDING->value) {
                                    $record->update(['status' => PRFMissionStatus::REJECTED->value]);
                                    $updated++;
                                }
                            }

                            if ($updated > 0) {
                                \Filament\Notifications\Notification::make()
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
                RelationManagers\MissionSubscriptionsRelationManager::class,
            ])
                ->icon('heroicon-o-user-group'),

            // Finance Group
            RelationGroup::make('Finance', [
                RelationManagers\RequisitionsRelationManager::class,
                RelationManagers\AccountingEventRelationManager::class,
            ])
                ->icon('heroicon-o-currency-dollar'),

            // Execution Group
            RelationGroup::make('Execution', [
                RelationManagers\MissionSessionsRelationManager::class,
                RelationManagers\WeatherForecastsRelationManager::class,
            ])
                ->icon('heroicon-o-play-circle'),

            // Outcomes Group
            RelationGroup::make('Outcomes', [
                RelationManagers\SoulsRelationManager::class,
                RelationManagers\DebriefNotesRelationManager::class,
                RelationManagers\MissionQuestionsRelationManager::class,
            ])
                ->icon('heroicon-o-clipboard-document-check'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMissions::route('/'),
            'create' => Pages\CreateMission::route('/create'),
            'view' => Pages\ViewMission::route('/{record}'),
            'edit' => Pages\EditMission::route('/{record}/edit'),
        ];
    }

    public static function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('New Mission')
                ->icon('heroicon-o-plus')
                ->visible(fn () => userCan('create mission')),
            \Filament\Actions\Action::make('export_missions')
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
