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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MissionQuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'missionQuestions';

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $label = 'Mission Question';

    protected static ?string $pluralLabel = 'Mission Questions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('❓ Question Details')
                    ->description('Add questions that arose during the mission')
                    ->schema([
                        Forms\Components\Textarea::make('question')
                            ->label('Question')
                            ->helperText('Enter the question that was asked or arose during the mission')
                            ->required()
                            ->rows(6)
                            ->placeholder('What question was asked during the mission?')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('🏷️ Question Classification')
                    ->description('Classify and categorize the question for better organization')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('category')
                                    ->label('Category')
                                    ->helperText('Select the category that best describes this question')
                                    ->options([
                                        'doctrinal' => '📖 Doctrinal/Biblical',
                                        'practical' => '🔧 Practical/Life Application',
                                        'theological' => '⛪ Theological',
                                        'personal' => '👤 Personal/Testimony',
                                        'general' => '💬 General Inquiry',
                                        'clarification' => '🔍 Clarification',
                                        'challenge' => '⚡ Challenge/Objection',
                                        'curiosity' => '🤔 Curiosity',
                                    ])
                                    ->placeholder('Select category'),

                                Forms\Components\Select::make('source')
                                    ->label('Question Source')
                                    ->helperText('Who asked this question?')
                                    ->options([
                                        'student' => '🎓 Student',
                                        'teacher' => '👨‍🏫 Teacher',
                                        'staff' => '👥 School Staff',
                                        'administration' => '🏛️ Administration',
                                        'missionary' => '✋ Missionary Team',
                                        'visitor' => '👋 Visitor',
                                        'other' => '❓ Other',
                                    ])
                                    ->placeholder('Select source'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('difficulty_level')
                                    ->label('Difficulty Level')
                                    ->helperText('How challenging was this question to answer?')
                                    ->options([
                                        'basic' => '🟢 Basic',
                                        'intermediate' => '🟡 Intermediate',
                                        'advanced' => '🔴 Advanced',
                                        'complex' => '🚨 Complex/Difficult',
                                    ])
                                    ->placeholder('Select difficulty'),

                                Forms\Components\Toggle::make('was_answered')
                                    ->label('Was Answered')
                                    ->helperText('Was this question answered during the mission?')
                                    ->default(false)
                                    ->live(),
                            ]),
                    ])->collapsible(),

                Forms\Components\Section::make('💬 Response & Follow-up')
                    ->description('Record the response given and any follow-up actions needed')
                    ->schema([
                        Forms\Components\Textarea::make('answer')
                            ->label('Answer Given')
                            ->helperText('The response that was provided to this question')
                            ->rows(5)
                            ->placeholder('What answer was given?')
                            ->visible(fn (callable $get) => $get('was_answered'))
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('follow_up_needed')
                            ->label('Follow-up Actions')
                            ->helperText('Any follow-up actions or research needed for this question')
                            ->rows(3)
                            ->placeholder('What follow-up is needed?')
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('related_topics')
                            ->label('Related Topics')
                            ->helperText('Topics or themes related to this question')
                            ->placeholder('Add related topics')
                            ->columnSpanFull(),
                    ])->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question')
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('📂 Category')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'doctrinal' => '📖 Doctrinal',
                        'practical' => '🔧 Practical',
                        'theological' => '⛪ Theological',
                        'personal' => '👤 Personal',
                        'general' => '💬 General',
                        'clarification' => '🔍 Clarification',
                        'challenge' => '⚡ Challenge',
                        'curiosity' => '🤔 Curiosity',
                        default => '💬 General',
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'doctrinal', 'theological' => Color::Blue,
                        'challenge' => Color::Red,
                        'practical' => Color::Green,
                        'personal' => Color::Purple,
                        default => Color::Gray,
                    })
                    ->sortable()
                    ->tooltip('Question category'),

                Tables\Columns\TextColumn::make('question')
                    ->label('❓ Question')
                    ->limit(80)
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn ($record) => $record->question),

                Tables\Columns\TextColumn::make('source')
                    ->label('👤 Source')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'student' => '🎓 Student',
                        'teacher' => '👨‍🏫 Teacher',
                        'staff' => '👥 Staff',
                        'administration' => '🏛️ Admin',
                        'missionary' => '✋ Missionary',
                        'visitor' => '👋 Visitor',
                        'other' => '❓ Other',
                        default => 'Not specified',
                    })
                    ->badge()
                    ->color(Color::Blue)
                    ->tooltip('Who asked this question'),

                Tables\Columns\TextColumn::make('difficulty_level')
                    ->label('🎯 Difficulty')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'basic' => '🟢 Basic',
                        'intermediate' => '🟡 Intermediate',
                        'advanced' => '🔴 Advanced',
                        'complex' => '🚨 Complex',
                        default => 'Not set',
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'basic' => Color::Green,
                        'intermediate' => Color::Yellow,
                        'advanced' => Color::Orange,
                        'complex' => Color::Red,
                        default => Color::Gray,
                    })
                    ->sortable()
                    ->tooltip('Question difficulty level'),

                Tables\Columns\IconColumn::make('was_answered')
                    ->label('✅ Answered')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor(Color::Green)
                    ->falseColor(Color::Gray)
                    ->tooltip(fn ($record) => $record->was_answered ? 'Question was answered' : 'Question not answered'),

                Tables\Columns\TextColumn::make('related_topics')
                    ->label('🏷️ Topics')
                    ->badge()
                    ->separator(',')
                    ->color(Color::Gray)
                    ->toggleable()
                    ->tooltip('Related topics'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Date question was recorded'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'doctrinal' => '📖 Doctrinal/Biblical',
                        'practical' => '🔧 Practical/Life Application',
                        'theological' => '⛪ Theological',
                        'personal' => '👤 Personal/Testimony',
                        'general' => '💬 General Inquiry',
                        'clarification' => '🔍 Clarification',
                        'challenge' => '⚡ Challenge/Objection',
                        'curiosity' => '🤔 Curiosity',
                    ]),

                Tables\Filters\SelectFilter::make('source')
                    ->label('Question Source')
                    ->options([
                        'student' => '🎓 Student',
                        'teacher' => '👨‍🏫 Teacher',
                        'staff' => '👥 School Staff',
                        'administration' => '🏛️ Administration',
                        'missionary' => '✋ Missionary Team',
                        'visitor' => '👋 Visitor',
                        'other' => '❓ Other',
                    ]),

                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label('Difficulty Level')
                    ->options([
                        'basic' => '🟢 Basic',
                        'intermediate' => '🟡 Intermediate',
                        'advanced' => '🔴 Advanced',
                        'complex' => '🚨 Complex/Difficult',
                    ]),

                Tables\Filters\TernaryFilter::make('was_answered')
                    ->label('Answer Status')
                    ->placeholder('All questions')
                    ->trueLabel('Answered')
                    ->falseLabel('Not answered'),

                Tables\Filters\TernaryFilter::make('needs_followup')
                    ->label('Needs Follow-up')
                    ->placeholder('All questions')
                    ->trueLabel('Needs follow-up')
                    ->falseLabel('No follow-up needed')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('follow_up_needed'),
                        false: fn ($query) => $query->whereNull('follow_up_needed'),
                    ),

                Tables\Filters\Filter::make('created_at')
                    ->label('Date Added')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'From: '.\Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until: '.\Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green)
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Question recorded')
                            ->body('Mission question has been successfully recorded.')
                            ->success()
                            ->send();
                    }),

            ])
            ->actions([
                Tables\Actions\Action::make('mark_answered')
                    ->label('Mark Answered')
                    ->icon('heroicon-o-check-circle')
                    ->color(Color::Green)
                    ->form([
                        Forms\Components\Textarea::make('answer')
                            ->label('Answer')
                            ->required()
                            ->rows(5),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'was_answered' => true,
                            'answer' => $data['answer'],
                        ]);

                        Notification::make()
                            ->title('Question marked as answered')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => ! $record->was_answered)
                    ->tooltip('Mark question as answered'),

                Tables\Actions\ViewAction::make()
                    ->color(Color::Gray),

                Tables\Actions\EditAction::make()
                    ->color(Color::Orange)
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Question updated')
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\BulkAction::make('mark_as_answered')
                        ->label('Mark as Answered')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['was_answered' => true]);
                            });

                            Notification::make()
                                ->title('Questions marked as answered')
                                ->body(count($records).' questions have been marked as answered.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('assign_category')
                        ->label('Assign Category')
                        ->icon('heroicon-o-tag')
                        ->color(Color::Blue)
                        ->form([
                            Forms\Components\Select::make('category')
                                ->label('Category')
                                ->options([
                                    'doctrinal' => '📖 Doctrinal/Biblical',
                                    'practical' => '🔧 Practical/Life Application',
                                    'theological' => '⛪ Theological',
                                    'personal' => '👤 Personal/Testimony',
                                    'general' => '💬 General Inquiry',
                                    'clarification' => '🔍 Clarification',
                                    'challenge' => '⚡ Challenge/Objection',
                                    'curiosity' => '🤔 Curiosity',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $record->update(['category' => $data['category']]);
                            });

                            Notification::make()
                                ->title('Category assigned')
                                ->body('Category has been assigned to '.count($records).' questions.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('export_questions')
                        ->label('Export Questions')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color(Color::Gray)
                        ->action(function ($records) {
                            // This would handle export
                            Notification::make()
                                ->title('Export started')
                                ->body('Questions export has been queued for processing.')
                                ->info()
                                ->send();
                        }),

                    Tables\Actions\RestoreBulkAction::make()
                        ->color(Color::Green),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->color(Color::Red),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
