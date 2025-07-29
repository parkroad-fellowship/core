<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Filament\Resources\MissionResource\Pages;
use App\Filament\Resources\MissionResource\RelationManagers;
use App\Jobs\Mission\EmailFinancialReportJob;
use App\Jobs\Mission\NotifySchoolOfMissionJob;
use App\Jobs\Mission\NotifyWhatsAppGroupJob;
use App\Jobs\Mission\RequestSchoolFeedbackJob;
use App\Models\Mission;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
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
                // Quick Actions Section - only visible on edit
                Forms\Components\Section::make('Quick Actions')
                    ->schema([
                        Actions::make([
                            Action::make('notify')
                                ->icon('heroicon-m-paper-airplane')
                                ->requiresConfirmation()
                                ->label('Notify school')
                                ->action(function ($record, $data) {
                                    NotifySchoolOfMissionJob::dispatch($record);
                                })
                                ->visible(fn ($record) => $record?->exists),
                            Action::make('feedback')
                                ->icon('heroicon-m-inbox-arrow-down')
                                ->requiresConfirmation()
                                ->label('Request feedback')
                                ->action(function ($record, $data) {
                                    RequestSchoolFeedbackJob::dispatch($record);
                                })
                                ->visible(fn ($record) => $record?->exists && $record->status >= PRFMissionStatus::SERVICED->value),
                            Action::make('expense-report')
                                ->icon('heroicon-m-arrow-down-tray')
                                ->label('Download expense report')
                                ->action(function ($record, $data) {
                                    $url = route('reports.mission-expenses.export', ['missionUlid' => $record->ulid]);

                                    return redirect($url);
                                })
                                ->visible(fn ($record) => $record?->exists),
                            Action::make('email-expense-report')
                                ->icon('heroicon-m-envelope')
                                ->requiresConfirmation()
                                ->label('Email expense report')
                                ->action(function ($record, $data) {
                                    EmailFinancialReportJob::dispatch($record);
                                })
                                ->visible(fn ($record) => $record?->exists),
                            Action::make('mission-report')
                                ->icon('heroicon-m-document-arrow-down')
                                ->label('Download mission report')
                                ->action(function ($record, $data) {
                                    $url = route('reports.missions.export', ['missionUlid' => $record->ulid]);

                                    return redirect($url);
                                })
                                ->visible(fn ($record) => $record?->exists),
                            Action::make('whatsapp-group')
                                ->icon('heroicon-m-chat-bubble-left-ellipsis')
                                ->requiresConfirmation()
                                ->label('Join WhatsApp group notification')
                                ->action(function ($record, $data) {
                                    NotifyWhatsAppGroupJob::dispatch($record);
                                })
                                ->visible(fn ($record) => $record?->exists && $record->status >= PRFMissionStatus::APPROVED->value),
                        ])->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record?->exists)
                    ->collapsible()
                    ->collapsed(),

                // Basic Information Section
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('ulid')
                            ->label('ULID')
                            ->visible(app()->isLocal())
                            ->disabled(),
                        Forms\Components\Select::make('school_term_id')
                            ->required()
                            ->relationship('schoolTerm', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('mission_type_id')
                            ->required()
                            ->relationship(
                                name: 'missionType',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                            )
                            ->searchable()
                            ->preload(),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('school_id')
                                    ->required()
                                    ->relationship(
                                        name: 'school',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->helperText('🏫 Select the school for this mission'),
                                Forms\Components\TextInput::make('capacity')
                                    ->label('Missionaries needed')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->helperText('👥 Number of missionaries required'),
                                Forms\Components\Select::make('status')
                                    ->required()
                                    ->options(PRFMissionStatus::getOptions())
                                    ->helperText('📊 Current mission status')
                                    ->default(PRFMissionStatus::PENDING->value)
                                    ->hiddenOn(['create'])
                                    ->live(),
                            ])->columns(3),
                        Forms\Components\Textarea::make('theme')
                            ->columnSpanFull()
                            ->required()
                            ->rows(3)
                            ->placeholder('Enter the mission theme or topic...'),
                    ])
                    ->columns(2),

                // Schedule Section
                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->timezone(Auth::user()->timezone)
                                    ->native(false)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Auto-set end_date if not already set
                                        if ($state) {
                                            $set('end_date', $state);
                                        }
                                    }),
                                Forms\Components\TimePicker::make('start_time')
                                    ->seconds(false)
                                    ->native(false)
                                    ->required()
                                    ->default('08:00')
                                    ->format('H:i'),
                                Forms\Components\DatePicker::make('end_date')
                                    ->timezone(Auth::user()->timezone)
                                    ->native(false)
                                    ->afterOrEqual('start_date'),
                                Forms\Components\TimePicker::make('end_time')
                                    ->seconds(false)
                                    ->required()
                                    ->native(false)
                                    ->default('17:00')
                                    ->format('H:i'),
                            ])->columns(4),
                    ]),

                // Communication Section
                Forms\Components\Section::make('Communication')
                    ->schema([
                        Forms\Components\TextInput::make('whats_app_link')
                            ->label('WhatsApp Group Link')
                            ->columnSpanFull()
                            ->url()
                            ->placeholder('https://chat.whatsapp.com/XXXXXXXXXX')
                            ->hint('Link to the WhatsApp group for this mission'),
                        Forms\Components\Repeater::make('offline_members')
                            ->label('Offline Members')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Name')
                                            ->required(),
                                        PhoneInput::make('phone_number')
                                            ->required(),
                                    ])->columns(2),
                            ])
                            ->addActionLabel('Add offline member')
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                // Mission Content Section
                Forms\Components\Section::make('Mission Content')
                    ->schema([
                        Forms\Components\MarkdownEditor::make('executive_summary')
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
                    ->visible(fn ($record) => intval($record?->status) === PRFMissionStatus::SERVICED->value || intval($record?->status) === PRFMissionStatus::POSTPONED->value),

                // Preparation Section
                Forms\Components\Section::make('Preparation')
                    ->schema([
                        Forms\Components\Textarea::make('mission_prep_notes')
                            ->columnSpanFull()
                            ->rows(4)
                            ->placeholder('Enter preparation notes for the mission...'),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Textarea::make('dressing_recommendations')
                                    ->hint('Generated by AI based on weather forecast. Mark mission as approved to generate automatically.')
                                    ->rows(4)
                                    ->placeholder('Dressing recommendations will appear here...'),
                                Forms\Components\Textarea::make('activity_recommendations')
                                    ->hint('Generated by AI based on weather forecast. Mark mission as approved to generate automatically.')
                                    ->rows(4)
                                    ->placeholder('Activity recommendations will appear here...'),
                            ])->columns(2),
                    ])
                    ->visible(fn ($record) => intval($record?->status) !== PRFMissionStatus::SERVICED->value)
                    ->collapsible(),

                // Status Information Section
                Forms\Components\Section::make('Status Information')
                    ->schema([
                        Forms\Components\Placeholder::make('mission_stats')
                            ->label('Mission Statistics')
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

                                $statusColor = match (true) {
                                    $percentage >= 100 => '🟢',
                                    $percentage >= 80 => '🟡',
                                    $percentage >= 50 => '🔵',
                                    default => '⚪',
                                };

                                return new \Illuminate\Support\HtmlString("
                                    <div class='space-y-2'>
                                        <div><strong>Total Subscribed:</strong> {$subscribed}</div>
                                        <div><strong>Approved:</strong> {$approved} / {$record->capacity} ({$percentage}%) {$statusColor}</div>
                                        <div><strong>Still Needed:</strong> {$needed}</div>
                                    </div>
                                ");
                            }),
                        Forms\Components\Toggle::make('teacher_feedback_requested_at')
                            ->label('Teacher Feedback Requested')
                            ->hint('Request teacher feedback using the action button above.')
                            ->disabled(true),
                    ])
                    ->visible(fn ($record) => $record?->exists),

                // Media Section
                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make(Mission::MISSION_PHOTOS)
                            ->label('Mission Photos')
                            ->multiple()
                            ->columnSpanFull()
                            ->collection(Mission::MISSION_PHOTOS)
                            ->disk(config('filament.default_filesystem_disk'))
                            ->acceptedFileTypes(['image/*'])
                            ->maxFiles(20)
                            ->hint('Upload photos from the mission. Maximum 20 files.'),
                    ])
                    ->visible(fn ($record) => $record?->exists)
                    ->collapsible(),
            ]);
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
                    Tables\Actions\Action::make('duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('warning')
                        ->action(function ($record) {
                            $newMission = $record->replicate([
                                'ulid',
                                'created_at',
                                'updated_at',
                                'deleted_at',
                                'start_date',
                                'end_date',
                                'status',
                                'teacher_feedback_requested_at',
                                'executive_summary',
                            ]);
                            $newMission->status = PRFMissionStatus::PENDING->value;
                            $newMission->save();

                            return redirect()->route('filament.admin.resources.missions.edit', $newMission);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Duplicate Mission')
                        ->modalDescription('This will create a new mission with the same details but reset status to pending.')
                        ->visible(fn () => userCan('create mission')),
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
            RelationManagers\MissionSubscriptionsRelationManager::class,
            RelationManagers\RequisitionsRelationManager::class,
            RelationManagers\MissionExpenseRelationManager::class,
            RelationManagers\ExpensesRelationManager::class,
            RelationManagers\WeatherForecastsRelationManager::class,
            RelationManagers\MissionSessionsRelationManager::class,
            RelationManagers\SoulsRelationManager::class,
            RelationManagers\DebriefNotesRelationManager::class,
            RelationManagers\MissionQuestionsRelationManager::class,
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
            ->with(['school', 'missionType', 'schoolTerm'])
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
