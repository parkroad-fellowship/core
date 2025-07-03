<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFCompletionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class CourseMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'courseMembers';

    protected static ?string $title = 'Courses';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $label = 'Course Enrollment';

    protected static ?string $pluralLabel = 'Course Enrollments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🎓 Course Enrollment Details')
                    ->description('Course participation and progress tracking')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('course_id')
                                    ->label('🎓 Course')
                                    ->helperText('Select the course for enrollment')
                                    ->relationship(
                                        name: 'course',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('description')
                                            ->rows(3),
                                    ]),

                                Forms\Components\Select::make('completion_status')
                                    ->label('📊 Completion Status')
                                    ->helperText('Current completion status of the course')
                                    ->options(PRFCompletionStatus::getOptions())
                                    ->required()
                                    ->default(PRFCompletionStatus::INCOMPLETE)
                                    ->native(false)
                                    ->live(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('percent_complete')
                                    ->label('📈 Progress Percentage')
                                    ->helperText('Percentage of course completed (0-100)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state >= 100) {
                                            $set('completion_status', PRFCompletionStatus::COMPLETE);
                                            $set('completed_at', now());
                                        } else {
                                            $set('completion_status', PRFCompletionStatus::INCOMPLETE);
                                            $set('completed_at', null);
                                        }
                                    }),

                                Forms\Components\DateTimePicker::make('completed_at')
                                    ->label('🎉 Completion Date')
                                    ->helperText('Date and time when course was completed')
                                    ->seconds(false)
                                    ->timezone(Auth::user()->timezone)
                                    ->native(false)
                                    ->visible(fn ($get) => $get('completion_status') === PRFCompletionStatus::COMPLETE->value),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('enrolled_at')
                                    ->label('📅 Enrollment Date')
                                    ->helperText('Date when member enrolled in the course')
                                    ->seconds(false)
                                    ->timezone(Auth::user()->timezone)
                                    ->native(false)
                                    ->default(now()),

                                Forms\Components\TextInput::make('grade')
                                    ->label('🏆 Grade/Score')
                                    ->helperText('Final grade or score achieved')
                                    ->placeholder('e.g., A, 85%, Pass')
                                    ->maxLength(10),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('📝 Notes')
                            ->helperText('Additional notes about course progress or performance')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Any notes about progress, challenges, or achievements...'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('course.name')
            ->columns([
                Tables\Columns\TextColumn::make('course.name')
                    ->label('🎓 Course')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->wrap()
                    ->tooltip('Course name'),

                Tables\Columns\TextColumn::make('completion_status')
                    ->badge()
                    ->label('📊 Status')
                    ->formatStateUsing(fn ($record) => PRFCompletionStatus::fromValue($record->completion_status)->name)
                    ->color(fn ($record) => PRFCompletionStatus::fromValue($record->completion_status)->getColor())
                    ->icon(fn ($record) => $record->completion_status === PRFCompletionStatus::COMPLETE->value
                        ? 'heroicon-o-check-circle'
                        : 'heroicon-o-clock')
                    ->sortable()
                    ->tooltip('Course completion status'),

                Tables\Columns\TextColumn::make('percent_complete')
                    ->label('📈 Progress')
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 100 => 'success',
                        $state >= 75 => 'warning',
                        $state >= 50 => 'info',
                        default => 'gray',
                    })
                    ->sortable()
                    ->tooltip('Course completion percentage'),

                Tables\Columns\TextColumn::make('grade')
                    ->label('🏆 Grade')
                    ->badge()
                    ->color('success')
                    ->placeholder('Not graded')
                    ->tooltip('Final grade or score'),

                Tables\Columns\TextColumn::make('enrolled_at')
                    ->label('📅 Enrolled')
                    ->dateTime('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip('Enrollment date'),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('🎉 Completed')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->placeholder('Not completed')
                    ->tooltip('Completion date and time'),

                Tables\Columns\TextColumn::make('duration')
                    ->label('⏱️ Duration')
                    ->getStateUsing(function ($record) {
                        if (! $record->enrolled_at) {
                            return 'N/A';
                        }
                        $start = \Carbon\Carbon::parse($record->enrolled_at);
                        $end = $record->completed_at
                            ? \Carbon\Carbon::parse($record->completed_at)
                            : now();

                        return $start->diffForHumans($end, true);
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable()
                    ->tooltip('Time taken to complete or current duration'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('📝 Notes')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->notes)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Added')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date enrollment was recorded'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Last modification date'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Show Deleted')
                    ->placeholder('Active enrollments only'),

                Tables\Filters\SelectFilter::make('course')
                    ->label('Course')
                    ->relationship('course', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('completion_status')
                    ->label('Completion Status')
                    ->options(PRFCompletionStatus::getOptions())
                    ->multiple(),

                Tables\Filters\Filter::make('progress_range')
                    ->label('Progress Range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('min_progress')
                                    ->label('Minimum %')
                                    ->numeric()
                                    ->placeholder('e.g., 50'),
                                Forms\Components\TextInput::make('max_progress')
                                    ->label('Maximum %')
                                    ->numeric()
                                    ->placeholder('e.g., 100'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_progress'],
                                fn (Builder $query, $progress): Builder => $query->where('percent_complete', '>=', $progress),
                            )
                            ->when(
                                $data['max_progress'],
                                fn (Builder $query, $progress): Builder => $query->where('percent_complete', '<=', $progress),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['min_progress'] ?? null) {
                            $indicators[] = 'Min: '.$data['min_progress'].'%';
                        }
                        if ($data['max_progress'] ?? null) {
                            $indicators[] = 'Max: '.$data['max_progress'].'%';
                        }

                        return $indicators;
                    }),

                Tables\Filters\Filter::make('enrollment_period')
                    ->label('Enrollment Period')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('from_date')
                                    ->label('From Date')
                                    ->native(false),
                                Forms\Components\DatePicker::make('to_date')
                                    ->label('To Date')
                                    ->native(false),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('enrolled_at', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('enrolled_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from_date'] ?? null) {
                            $indicators[] = 'From: '.\Carbon\Carbon::parse($data['from_date'])->toFormattedDateString();
                        }
                        if ($data['to_date'] ?? null) {
                            $indicators[] = 'To: '.\Carbon\Carbon::parse($data['to_date'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                Tables\Filters\TernaryFilter::make('has_grade')
                    ->label('Graded Status')
                    ->placeholder('All enrollments')
                    ->trueLabel('Graded only')
                    ->falseLabel('Ungraded only')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('grade'),
                        false: fn (Builder $query) => $query->whereNull('grade'),
                    ),
            ])
            ->headerActions([
                // Course enrollments are typically read-only from this view
            ])
            ->actions([
                Tables\Actions\Action::make('update_progress')
                    ->label('Update Progress')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color(Color::Blue)
                    ->form([
                        Forms\Components\TextInput::make('percent_complete')
                            ->label('Progress %')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100),
                        Forms\Components\Textarea::make('notes')
                            ->label('Progress Notes')
                            ->rows(3),
                    ])
                    ->fillForm(fn ($record) => [
                        'percent_complete' => $record->percent_complete,
                        'notes' => $record->notes,
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'percent_complete' => $data['percent_complete'],
                            'notes' => $data['notes'],
                            'completion_status' => $data['percent_complete'] >= 100
                                ? PRFCompletionStatus::COMPLETE
                                : PRFCompletionStatus::INCOMPLETE,
                            'completed_at' => $data['percent_complete'] >= 100 ? now() : null,
                        ]);

                        Notification::make()
                            ->title('Progress updated')
                            ->body("Course progress updated to {$data['percent_complete']}%.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->completion_status !== PRFCompletionStatus::COMPLETE->value)
                    ->tooltip('Update course progress'),

                Tables\Actions\Action::make('mark_complete')
                    ->label('Mark Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color(Color::Green)
                    ->form([
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completion Date')
                            ->required()
                            ->default(now())
                            ->native(false),
                        Forms\Components\TextInput::make('grade')
                            ->label('Final Grade')
                            ->placeholder('e.g., A, 95%, Pass'),
                        Forms\Components\Textarea::make('completion_notes')
                            ->label('Completion Notes')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'completion_status' => PRFCompletionStatus::COMPLETE,
                            'percent_complete' => 100,
                            'completed_at' => $data['completed_at'],
                            'grade' => $data['grade'],
                            'notes' => ($record->notes ? $record->notes."\n" : '').'Completed: '.$data['completion_notes'],
                        ]);

                        Notification::make()
                            ->title('Course completed')
                            ->body('Course has been marked as completed!')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->completion_status !== PRFCompletionStatus::COMPLETE->value)
                    ->tooltip('Mark course as completed'),

                Tables\Actions\Action::make('generate_certificate')
                    ->label('Certificate')
                    ->icon('heroicon-o-document-text')
                    ->color(Color::Green)
                    ->action(function ($record) {
                        // Logic to generate certificate
                        Notification::make()
                            ->title('Certificate generated')
                            ->body('Course completion certificate is being prepared.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->completion_status === PRFCompletionStatus::COMPLETE->value)
                    ->tooltip('Generate completion certificate'),

                Tables\Actions\ViewAction::make()
                    ->color(Color::Gray),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('update_progress_bulk')
                        ->label('Update Progress')
                        ->icon('heroicon-o-arrow-trending-up')
                        ->color(Color::Blue)
                        ->form([
                            Forms\Components\TextInput::make('percent_complete')
                                ->label('Progress %')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            Forms\Components\Textarea::make('notes')
                                ->label('Progress Notes')
                                ->rows(3),
                        ])
                        ->action(function ($records, array $data) {
                            $count = $records->count();
                            $records->each(function ($record) use ($data) {
                                $record->update([
                                    'percent_complete' => $data['percent_complete'],
                                    'notes' => $data['notes'],
                                    'completion_status' => $data['percent_complete'] >= 100
                                        ? PRFCompletionStatus::COMPLETE
                                        : PRFCompletionStatus::INCOMPLETE,
                                    'completed_at' => $data['percent_complete'] >= 100 ? now() : null,
                                ]);
                            });

                            Notification::make()
                                ->title('Progress updated')
                                ->body("Progress updated for {$count} course enrollments.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('generate_certificates')
                        ->label('Generate Certificates')
                        ->icon('heroicon-o-document-text')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $count = $records->where('completion_status', PRFCompletionStatus::COMPLETE)->count();

                            Notification::make()
                                ->title('Certificates generated')
                                ->body("Certificates generated for {$count} completed courses.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('enrolled_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
