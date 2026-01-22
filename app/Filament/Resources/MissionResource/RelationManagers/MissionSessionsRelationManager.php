<?php

namespace App\Filament\Resources\MissionResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Carbon\Carbon;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TimePicker;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MissionSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'missionSessions';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $title = '🎓 Sessions';

    protected static ?string $label = 'Mission Session';

    protected static ?string $pluralLabel = 'Mission Sessions';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->missionSessions()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('🏫 Session Details')
                    ->description('Basic session information and timing')
                    ->schema([
                        TextInput::make('ulid')
                            ->label('Session ID')
                            ->helperText('Unique identifier for this session')
                            ->visible(app()->isLocal())
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('class_group_id')
                            ->label('Class Group')
                            ->helperText('Select the class group for this session')
                            ->relationship('classGroup', 'name')
                            ->searchable()
                            ->preload(),

                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('starts_at')
                                    ->label('⏰ Start Time')
                                    ->helperText('When the session starts')
                                    ->required()
                                    ->seconds(false)
                                    ->native(false)
                                    ->timezone(Auth::user()->timezone)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Auto-set end time to 1 hour later if not set
                                        if ($state && ! $get('ends_at')) {
                                            $set('ends_at', Carbon::parse($state)->addHour());
                                        }
                                    }),

                                DateTimePicker::make('ends_at')
                                    ->label('⏰ End Time')
                                    ->helperText('When the session ends')
                                    ->required()
                                    ->seconds(false)
                                    ->native(false)
                                    ->timezone(Auth::user()->timezone)
                                    ->afterOrEqual('starts_at'),
                            ]),
                    ]),

                Section::make('👥 Session Team')
                    ->description('Select facilitator and speaker for this session')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('facilitator_id')
                                    ->label('🎯 Facilitator')
                                    ->helperText('Mission member who will facilitate this session')
                                    ->relationship(
                                        name: 'facilitator',
                                        titleAttribute: 'full_name',
                                        modifyQueryUsing: fn (Builder $query) => $query->whereHas('missionSubscriptions',
                                            fn (Builder $query) => $query->where('mission_id', $this->ownerRecord->id)
                                        ),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('speaker_id')
                                    ->label('🎤 Speaker')
                                    ->helperText('Mission member who will speak during this session')
                                    ->relationship(
                                        name: 'speaker',
                                        titleAttribute: 'full_name',
                                        modifyQueryUsing: fn (Builder $query) => $query->whereHas('missionSubscriptions',
                                            fn (Builder $query) => $query->where('mission_id', $this->ownerRecord->id)
                                        ),
                                    )
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),

                Section::make('📝 Session Notes')
                    ->description('Additional notes and observations for this session')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Session Notes')
                            ->helperText('Any additional notes, observations, or special instructions for this session')
                            ->rows(5)
                            ->required()
                            ->placeholder('Enter notes about session preparation, special requirements, or observations...')
                            ->columnSpanFull(),
                    ])->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('facilitator_id')
            ->columns([
                TextColumn::make('classGroup.name')
                    ->label('🏫 Class')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(Color::Blue)
                    ->placeholder('Not assigned')
                    ->tooltip('Class group for this session'),

                TextColumn::make('facilitator.full_name')
                    ->label('🎯 Facilitator')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->facilitator?->phone_number)
                    ->placeholder('Not assigned')
                    ->tooltip('Session facilitator'),

                TextColumn::make('speaker.full_name')
                    ->label('🎤 Speaker')
                    ->searchable()
                    ->placeholder('No speaker')
                    ->color(fn ($record) => $record->speaker_id ? null : Color::Gray)
                    ->tooltip('Session speaker'),

                TextColumn::make('starts_at')
                    ->label('⏰ Time')
                    ->dateTime('M j, g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->description(fn ($record) => $record->ends_at
                        ? '→ '.Carbon::parse($record->ends_at)->timezone(Auth::user()->timezone)->format('g:i A')
                        : null
                    )
                    ->tooltip('Session start and end time'),

                TextColumn::make('duration')
                    ->label('⏱️ Duration')
                    ->getStateUsing(fn ($record) => $record->starts_at && $record->ends_at
                        ? Carbon::parse($record->starts_at)->diffInMinutes($record->ends_at).' min'
                        : 'N/A'
                    )
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        ! $record->starts_at || ! $record->ends_at => Color::Gray,
                        Carbon::parse($record->starts_at)->diffInMinutes($record->ends_at) > 60 => Color::Green,
                        Carbon::parse($record->starts_at)->diffInMinutes($record->ends_at) > 30 => Color::Blue,
                        default => Color::Yellow,
                    })
                    ->tooltip('Session duration'),

                IconColumn::make('has_notes')
                    ->label('📝')
                    ->getStateUsing(fn ($record) => ! empty($record->notes))
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor(Color::Green)
                    ->falseColor(Color::Gray)
                    ->tooltip(fn ($record) => $record->notes ? 'Has notes: '.substr($record->notes, 0, 100).'...' : 'No notes'),

                TextColumn::make('created_at')
                    ->label('📅 Created')
                    ->dateTime('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date session was created'),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('class_group_id')
                    ->label('Class Group')
                    ->relationship('classGroup', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('facilitator_id')
                    ->label('Facilitator')
                    ->relationship('facilitator', 'full_name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('speaker_id')
                    ->label('Speaker')
                    ->relationship('speaker', 'full_name')
                    ->searchable()
                    ->preload(),

                Filter::make('session_date')
                    ->label('Session Date')
                    ->schema([
                        DatePicker::make('session_from')
                            ->native(false)
                            ->label('From Date'),
                        DatePicker::make('session_until')
                            ->native(false)
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['session_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '>=', $date),
                            )
                            ->when(
                                $data['session_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['session_from'] ?? null) {
                            $indicators[] = 'From: '.Carbon::parse($data['session_from'])->toFormattedDateString();
                        }
                        if ($data['session_until'] ?? null) {
                            $indicators[] = 'Until: '.Carbon::parse($data['session_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                TernaryFilter::make('has_speaker')
                    ->label('Has Speaker')
                    ->placeholder('All sessions')
                    ->trueLabel('With speaker')
                    ->falseLabel('Without speaker')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('speaker_id'),
                        false: fn (Builder $query) => $query->whereNull('speaker_id'),
                    ),

                TernaryFilter::make('has_notes')
                    ->label('Has Notes')
                    ->placeholder('All sessions')
                    ->trueLabel('With notes')
                    ->falseLabel('Without notes')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('notes'),
                        false: fn (Builder $query) => $query->whereNull('notes'),
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green)
                    ->label('Add Session')
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Session created successfully')
                            ->body('Mission session has been scheduled.')
                            ->success()
                            ->send();
                    }),

                Action::make('auto_schedule')
                    ->label('Auto Schedule')
                    ->icon('heroicon-o-calendar-days')
                    ->color(Color::Blue)
                    ->schema([
                        Select::make('class_group_ids')
                            ->label('Class Groups')
                            ->relationship('classGroup', 'name')
                            ->multiple()
                            ->preload()
                            ->required(),
                        TimePicker::make('start_time')
                            ->label('Start Time')
                            ->required()
                            ->seconds(false),
                        TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->default(45)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        Notification::make()
                            ->title('Sessions scheduled')
                            ->body('Auto-scheduling feature coming soon.')
                            ->info()
                            ->send();
                    })
                    ->visible(fn () => userCan('create mission session')),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color(Color::Blue)
                        ->action(function ($record) {
                            $newSession = $record->replicate();
                            $newSession->starts_at = Carbon::parse($record->starts_at)->addHour();
                            $newSession->ends_at = Carbon::parse($record->ends_at)->addHour();
                            $newSession->save();

                            Notification::make()
                                ->title('Session duplicated')
                                ->body('A copy of the session has been created.')
                                ->success()
                                ->send();
                        }),

                    ViewAction::make()
                        ->color(Color::Gray),

                    EditAction::make()
                        ->color(Color::Orange)
                        ->after(function ($record) {
                            Notification::make()
                                ->title('Session updated')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()
                        ->color(Color::Red),

                    ForceDeleteAction::make()
                        ->color(Color::Red),

                    RestoreAction::make()
                        ->color(Color::Green),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->color(Color::Red),

                    BulkAction::make('assign_facilitator')
                        ->label('Assign Facilitator')
                        ->icon('heroicon-o-user-plus')
                        ->color(Color::Blue)
                        ->form([
                            Select::make('facilitator_id')
                                ->label('Facilitator')
                                ->relationship(
                                    name: 'facilitator',
                                    titleAttribute: 'full_name',
                                    modifyQueryUsing: fn (Builder $query) => $query->whereHas('missionSubscriptions',
                                        fn (Builder $query) => $query->where('mission_id', $this->ownerRecord->id)
                                    ),
                                )
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $record->update(['facilitator_id' => $data['facilitator_id']]);
                            });

                            Notification::make()
                                ->title('Facilitator assigned')
                                ->body('Facilitator has been assigned to '.count($records).' sessions.')
                                ->success()
                                ->send();
                        }),

                    ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    RestoreBulkAction::make()
                        ->color(Color::Green),
                ]),
            ])
            ->defaultSort('starts_at', 'asc')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['classGroup', 'facilitator', 'speaker'])
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ])
            );
    }
}
