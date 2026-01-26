<?php

namespace App\Filament\Resources\Members\RelationManagers;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFCompletionStatus;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('🎓 Course Enrollment Details')
                    ->description('Course participation and progress tracking')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                Select::make('course_id')
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
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Textarea::make('description')
                                            ->rows(3),
                                    ]),

                                Select::make('completion_status')
                                    ->label('📊 Completion Status')
                                    ->helperText('Current completion status of the course')
                                    ->options(PRFCompletionStatus::getOptions())
                                    ->required()
                                    ->default(PRFCompletionStatus::INCOMPLETE)
                                    ->native(false)
                                    ->live(),
                            ]),

                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('percent_complete')
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

                                DateTimePicker::make('completed_at')
                                    ->label('🎉 Completion Date')
                                    ->helperText('Date and time when course was completed')
                                    ->seconds(false)
                                    ->timezone(Auth::user()->timezone)
                                    ->native(false)
                                    ->visible(fn ($get) => $get('completion_status') === PRFCompletionStatus::COMPLETE->value),
                            ]),

                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                DateTimePicker::make('enrolled_at')
                                    ->label('📅 Enrollment Date')
                                    ->helperText('Date when member enrolled in the course')
                                    ->seconds(false)
                                    ->timezone(Auth::user()->timezone)
                                    ->native(false)
                                    ->default(now()),

                                TextInput::make('grade')
                                    ->label('🏆 Grade/Score')
                                    ->helperText('Final grade or score achieved')
                                    ->placeholder('e.g., A, 85%, Pass')
                                    ->maxLength(10),
                            ]),

                        Textarea::make('notes')
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
                TextColumn::make('course.name')
                    ->label('🎓 Course')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->wrap()
                    ->tooltip('Course name'),

                TextColumn::make('completion_status')
                    ->badge()
                    ->label('📊 Status')
                    ->formatStateUsing(fn ($record) => PRFCompletionStatus::fromValue($record->completion_status)->name)
                    ->color(fn ($record) => PRFCompletionStatus::fromValue($record->completion_status)->getColor())
                    ->icon(fn ($record) => $record->completion_status === PRFCompletionStatus::COMPLETE->value
                        ? 'heroicon-o-check-circle'
                        : 'heroicon-o-clock')
                    ->sortable()
                    ->tooltip('Course completion status'),

                TextColumn::make('percent_complete')
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

                TextColumn::make('grade')
                    ->label('🏆 Grade')
                    ->badge()
                    ->color('success')
                    ->placeholder('Not graded')
                    ->tooltip('Final grade or score'),

                TextColumn::make('enrolled_at')
                    ->label('📅 Enrolled')
                    ->dateTime('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip('Enrollment date'),

                TextColumn::make('completed_at')
                    ->label('🎉 Completed')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->placeholder('Not completed')
                    ->tooltip('Completion date and time'),

                TextColumn::make('duration')
                    ->label('⏱️ Duration')
                    ->getStateUsing(function ($record) {
                        if (! $record->enrolled_at) {
                            return 'N/A';
                        }
                        $start = Carbon::parse($record->enrolled_at);
                        $end = $record->completed_at
                            ? Carbon::parse($record->completed_at)
                            : now();

                        return $start->diffForHumans($end, true);
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable()
                    ->tooltip('Time taken to complete or current duration'),

                TextColumn::make('notes')
                    ->label('📝 Notes')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->notes)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('📅 Added')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date enrollment was recorded'),

                TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Last modification date'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Show Deleted')
                    ->placeholder('Active enrollments only'),

                SelectFilter::make('course')
                    ->label('Course')
                    ->relationship('course', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('completion_status')
                    ->label('Completion Status')
                    ->options(PRFCompletionStatus::getOptions())
                    ->multiple(),

                Filter::make('progress_range')
                    ->label('Progress Range')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('min_progress')
                                    ->label('Minimum %')
                                    ->numeric()
                                    ->placeholder('e.g., 50'),
                                TextInput::make('max_progress')
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

                Filter::make('enrollment_period')
                    ->label('Enrollment Period')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                DatePicker::make('from_date')
                                    ->label('From Date')
                                    ->native(false),
                                DatePicker::make('to_date')
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
                            $indicators[] = 'From: '.Carbon::parse($data['from_date'])->toFormattedDateString();
                        }
                        if ($data['to_date'] ?? null) {
                            $indicators[] = 'To: '.Carbon::parse($data['to_date'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                TernaryFilter::make('has_grade')
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
            ->recordActions([
                Action::make('update_progress')
                    ->label('Update Progress')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color(Color::Blue)
                    ->schema([
                        TextInput::make('percent_complete')
                            ->label('Progress %')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100),
                        Textarea::make('notes')
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

                Action::make('mark_complete')
                    ->label('Mark Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color(Color::Green)
                    ->schema([
                        DateTimePicker::make('completed_at')
                            ->label('Completion Date')
                            ->required()
                            ->default(now())
                            ->native(false),
                        TextInput::make('grade')
                            ->label('Final Grade')
                            ->placeholder('e.g., A, 95%, Pass'),
                        Textarea::make('completion_notes')
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

                Action::make('generate_certificate')
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

                ViewAction::make()
                    ->color(Color::Gray),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('update_progress_bulk')
                        ->label('Update Progress')
                        ->icon('heroicon-o-arrow-trending-up')
                        ->color(Color::Blue)
                        ->form([
                            TextInput::make('percent_complete')
                                ->label('Progress %')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100),
                            Textarea::make('notes')
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

                    BulkAction::make('generate_certificates')
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
