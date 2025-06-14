<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFMissionStatus;
use App\Filament\Resources\MissionResource\Pages;
use App\Filament\Resources\MissionResource\RelationManagers;
use App\Jobs\Mission\EmailFinancialReportJob;
use App\Jobs\Mission\NotifySchoolOfMissionJob;
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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MissionResource extends Resource
{
    protected static ?string $model = Mission::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Missions Secretary';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Actions::make([
                    Action::make('notify')
                        ->icon('heroicon-m-paper-airplane')
                        ->requiresConfirmation()
                        ->label('Notify school')
                        ->action(function ($record, $data) {
                            NotifySchoolOfMissionJob::dispatch($record);
                        }),
                    Action::make('feedback')
                        ->icon('heroicon-m-inbox-arrow-down')
                        ->requiresConfirmation()
                        ->label('Request feedback')
                        ->action(function ($record, $data) {
                            RequestSchoolFeedbackJob::dispatch($record);
                        }),
                    Action::make('expense-report')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->label('Download expense report')
                        ->action(function ($record, $data) {
                            // Open a new tab and navigate
                            $url = route('reports.mission-expenses.export', ['missionUlid' => $record->ulid]);

                            // Open the URL in a new tab
                            return redirect($url);
                        }),
                    Action::make('email-expense-report')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->requiresConfirmation()
                        ->label('Email expense report')
                        ->action(function ($record, $data) {
                            EmailFinancialReportJob::dispatch($record);
                        }),

                    Action::make('mission-report')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->label('Download mission report')
                        ->action(function ($record, $data) {
                            // Open a new tab and navigate
                            $url = route('reports.missions.export', ['missionUlid' => $record->ulid]);

                            // Open the URL in a new tab
                            return redirect($url);
                        }),
                ])->columnSpanFull(),
                Forms\Components\TextInput::make('ulid')
                    ->required()
                    ->label('ULID')
                    ->visible(app()->isLocal())
                    ->disabled(),
                Forms\Components\Select::make('school_term_id')
                    ->required()
                    ->relationship('schoolTerm', 'name'),
                Forms\Components\Select::make('mission_type_id')
                    ->required()
                    ->relationship(
                        name: 'missionType',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                    ),
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Select::make('school_id')
                            ->required()
                            ->relationship(
                                name: 'school',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                            )
                            ->searchable(),
                        Forms\Components\TextInput::make('capacity')
                            ->label('Missionaries needed')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options(PRFMissionStatus::getOptions())
                            ->default(PRFMissionStatus::PENDING->value),
                    ])->columns(3),
                Forms\Components\DatePicker::make('start_date')
                    ->timezone(Auth::user()->timezone)
                    ->native(false)
                    ->required(),
                Forms\Components\TimePicker::make('start_time')
                    ->seconds(false)
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->timezone(Auth::user()->timezone)
                    ->native(false),
                Forms\Components\TimePicker::make('end_time')
                    ->seconds(false)
                    ->required(),
                Forms\Components\Textarea::make('theme')
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\TextInput::make('whats_app_link')
                    ->label('WhatsApp link')
                    ->columnSpanFull()
                    ->url()
                    ->required(),

                Forms\Components\MarkdownEditor::make('executive_summary')
                    ->columnSpanFull()
                    ->visible(fn($record) => intval($record?->status) === PRFMissionStatus::SERVICED->value || intval($record?->status) === PRFMissionStatus::POSTPONED->value),
                // Only show the preparation section if the mission is not serviced
                Forms\Components\Section::make('Preparation')
                    ->schema([
                        Forms\Components\Textarea::make('mission_prep_notes')
                            ->columnSpanFull()
                            ->rows(5),
                        Forms\Components\Textarea::make('dressing_recommendations')
                            ->hint('This is generated by Gemini based on the weather forecast. Mark the mission as approved to generate this. You can edit after it is generated.')
                            ->rows(5),
                        Forms\Components\Textarea::make('activity_recommendations')
                            ->hint('This is generated by Gemini based on the weather forecast. Mark the mission as approved to generate this. You can edit after it is generated.')
                            ->rows(5),
                    ])->visible(fn($record) => intval($record?->status) !== PRFMissionStatus::SERVICED->value),
                Forms\Components\Section::make('Info')
                    ->schema([
                        Forms\Components\Toggle::make('teacher_feedback_requested_at')
                            ->label('Teacher Feedback Requested')
                            ->hint('If this is grey, you can request teacher feedback by clicking the `Request feedback` button above.')
                            ->disabled(true),
                    ]),
                Forms\Components\SpatieMediaLibraryFileUpload::make(Mission::MISSION_PHOTOS)
                    ->label('Mission Photos')
                    ->multiple()
                    ->columnSpanFull()
                    ->collection(Mission::MISSION_PHOTOS)
                    ->disk(config('media-library.disk_name')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('schoolTerm.name'),
                Tables\Columns\TextColumn::make('school.name')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('missionType.name')
                    ->wrap(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->timezone(Auth::user()->timezone),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->timezone(Auth::user()->timezone),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn($record) => PRFMissionStatus::fromValue($record->status)->name)
                    ->sortable(),
                Tables\Columns\TextColumn::make('mission_subscriptions_count')
                    ->label('Subscriptions')
                    ->counts('missionSubscriptions')
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
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        PRFMissionStatus::PENDING->value => 'Pending',
                        PRFMissionStatus::APPROVED->value => 'Approved',
                        PRFMissionStatus::FULLY_SUBSCRIBED->value => 'Fully Subscribed',
                        PRFMissionStatus::REJECTED->value => 'Rejected',
                        PRFMissionStatus::CANCELLED->value => 'Cancelled',
                        PRFMissionStatus::SERVICED->value => 'Serviced',
                    ])
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
                        modifyQueryUsing: fn($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->visible(fn() => userCan('view mission')),
                Tables\Actions\EditAction::make()->visible(fn() => userCan('edit mission')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ])->visible(fn() => userCan('delete mission')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MissionSubscriptionsRelationManager::class,
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
