<?php

namespace App\Filament\Resources\MissionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🏫 Session Details')
                    ->description('Basic session information and timing')
                    ->schema([
                        Forms\Components\TextInput::make('ulid')
                            ->label('Session ID')
                            ->helperText('Unique identifier for this session')
                            ->visible(app()->isLocal())
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('class_group_id')
                            ->label('Class Group')
                            ->helperText('Select the class group for this session')
                            ->relationship('classGroup', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
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
                                            $set('ends_at', \Carbon\Carbon::parse($state)->addHour());
                                        }
                                    }),

                                Forms\Components\DateTimePicker::make('ends_at')
                                    ->label('⏰ End Time')
                                    ->helperText('When the session ends')
                                    ->required()
                                    ->seconds(false)
                                    ->native(false)
                                    ->timezone(Auth::user()->timezone)
                                    ->afterOrEqual('starts_at'),
                            ]),
                    ]),

                Forms\Components\Section::make('👥 Session Team')
                    ->description('Select facilitator and speaker for this session')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('facilitator_id')
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

                                Forms\Components\Select::make('speaker_id')
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

                Forms\Components\Section::make('📝 Session Notes')
                    ->description('Additional notes and observations for this session')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
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
                Tables\Columns\TextColumn::make('classGroup.name')
                    ->label('🏫 Class')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(Color::Blue)
                    ->placeholder('Not assigned')
                    ->tooltip('Class group for this session'),

                Tables\Columns\TextColumn::make('facilitator.full_name')
                    ->label('🎯 Facilitator')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->facilitator?->phone_number)
                    ->placeholder('Not assigned')
                    ->tooltip('Session facilitator'),

                Tables\Columns\TextColumn::make('speaker.full_name')
                    ->label('🎤 Speaker')
                    ->searchable()
                    ->placeholder('No speaker')
                    ->color(fn ($record) => $record->speaker_id ? null : Color::Gray)
                    ->tooltip('Session speaker'),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('⏰ Time')
                    ->dateTime('M j, g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->description(fn ($record) => $record->ends_at
                        ? '→ '.\Carbon\Carbon::parse($record->ends_at)->timezone(Auth::user()->timezone)->format('g:i A')
                        : null
                    )
                    ->tooltip('Session start and end time'),

                Tables\Columns\TextColumn::make('duration')
                    ->label('⏱️ Duration')
                    ->getStateUsing(fn ($record) => $record->starts_at && $record->ends_at
                        ? \Carbon\Carbon::parse($record->starts_at)->diffInMinutes($record->ends_at).' min'
                        : 'N/A'
                    )
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        ! $record->starts_at || ! $record->ends_at => Color::Gray,
                        \Carbon\Carbon::parse($record->starts_at)->diffInMinutes($record->ends_at) > 60 => Color::Green,
                        \Carbon\Carbon::parse($record->starts_at)->diffInMinutes($record->ends_at) > 30 => Color::Blue,
                        default => Color::Yellow,
                    })
                    ->tooltip('Session duration'),

                Tables\Columns\IconColumn::make('has_notes')
                    ->label('📝')
                    ->getStateUsing(fn ($record) => ! empty($record->notes))
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor(Color::Green)
                    ->falseColor(Color::Gray)
                    ->tooltip(fn ($record) => $record->notes ? 'Has notes: '.substr($record->notes, 0, 100).'...' : 'No notes'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Created')
                    ->dateTime('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date session was created'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\SelectFilter::make('class_group_id')
                    ->label('Class Group')
                    ->relationship('classGroup', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('facilitator_id')
                    ->label('Facilitator')
                    ->relationship('facilitator', 'full_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('speaker_id')
                    ->label('Speaker')
                    ->relationship('speaker', 'full_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('session_date')
                    ->label('Session Date')
                    ->form([
                        Forms\Components\DatePicker::make('session_from')
                            ->native(false)
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('session_until')
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
                            $indicators[] = 'From: '.\Carbon\Carbon::parse($data['session_from'])->toFormattedDateString();
                        }
                        if ($data['session_until'] ?? null) {
                            $indicators[] = 'Until: '.\Carbon\Carbon::parse($data['session_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                Tables\Filters\TernaryFilter::make('has_speaker')
                    ->label('Has Speaker')
                    ->placeholder('All sessions')
                    ->trueLabel('With speaker')
                    ->falseLabel('Without speaker')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('speaker_id'),
                        false: fn (Builder $query) => $query->whereNull('speaker_id'),
                    ),

                Tables\Filters\TernaryFilter::make('has_notes')
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
                Tables\Actions\CreateAction::make()
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

                Tables\Actions\Action::make('auto_schedule')
                    ->label('Auto Schedule')
                    ->icon('heroicon-o-calendar-days')
                    ->color(Color::Blue)
                    ->form([
                        Forms\Components\Select::make('class_group_ids')
                            ->label('Class Groups')
                            ->relationship('classGroup', 'name')
                            ->multiple()
                            ->preload()
                            ->required(),
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Start Time')
                            ->required()
                            ->seconds(false),
                        Forms\Components\TextInput::make('duration_minutes')
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
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color(Color::Blue)
                        ->action(function ($record) {
                            $newSession = $record->replicate();
                            $newSession->starts_at = \Carbon\Carbon::parse($record->starts_at)->addHour();
                            $newSession->ends_at = \Carbon\Carbon::parse($record->ends_at)->addHour();
                            $newSession->save();

                            Notification::make()
                                ->title('Session duplicated')
                                ->body('A copy of the session has been created.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\ViewAction::make()
                        ->color(Color::Gray),

                    Tables\Actions\EditAction::make()
                        ->color(Color::Orange)
                        ->after(function ($record) {
                            Notification::make()
                                ->title('Session updated')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->color(Color::Red),

                    Tables\Actions\ForceDeleteAction::make()
                        ->color(Color::Red),

                    Tables\Actions\RestoreAction::make()
                        ->color(Color::Green),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\BulkAction::make('assign_facilitator')
                        ->label('Assign Facilitator')
                        ->icon('heroicon-o-user-plus')
                        ->color(Color::Blue)
                        ->form([
                            Forms\Components\Select::make('facilitator_id')
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

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\RestoreBulkAction::make()
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
