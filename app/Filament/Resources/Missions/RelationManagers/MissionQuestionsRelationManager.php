<?php

namespace App\Filament\Resources\Missions\RelationManagers;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MissionQuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'missionQuestions';

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $title = '❓ Questions';

    protected static ?string $label = 'Mission Question';

    protected static ?string $pluralLabel = 'Mission Questions';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->missionQuestions()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        $unanswered = $ownerRecord->missionQuestions()->whereNull('answer')->count();

        return $unanswered > 0 ? 'warning' : 'success';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('❓ Question Details')
                    ->description('Add questions that arose during the mission')
                    ->schema([
                        Textarea::make('question')
                            ->label('Question')
                            ->helperText('Enter the question that was asked or arose during the mission')
                            ->required()
                            ->rows(6)
                            ->placeholder('What question was asked during the mission?')
                            ->columnSpanFull(),
                    ]),

                Section::make('🏷️ Question Classification')
                    ->description('Classify and categorize the question for better organization')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('category')
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

                                Select::make('source')
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

                        Grid::make(2)
                            ->schema([
                                Select::make('difficulty_level')
                                    ->label('Difficulty Level')
                                    ->helperText('How challenging was this question to answer?')
                                    ->options([
                                        'basic' => '🟢 Basic',
                                        'intermediate' => '🟡 Intermediate',
                                        'advanced' => '🔴 Advanced',
                                        'complex' => '🚨 Complex/Difficult',
                                    ])
                                    ->placeholder('Select difficulty'),

                                Toggle::make('was_answered')
                                    ->label('Was Answered')
                                    ->helperText('Was this question answered during the mission?')
                                    ->default(false)
                                    ->live(),
                            ]),
                    ])->collapsible(),

                Section::make('💬 Response & Follow-up')
                    ->description('Record the response given and any follow-up actions needed')
                    ->schema([
                        Textarea::make('answer')
                            ->label('Answer Given')
                            ->helperText('The response that was provided to this question')
                            ->rows(5)
                            ->placeholder('What answer was given?')
                            ->visible(fn (callable $get) => $get('was_answered'))
                            ->columnSpanFull(),

                        Textarea::make('follow_up_needed')
                            ->label('Follow-up Actions')
                            ->helperText('Any follow-up actions or research needed for this question')
                            ->rows(3)
                            ->placeholder('What follow-up is needed?')
                            ->columnSpanFull(),

                        TagsInput::make('related_topics')
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
                TextColumn::make('category')
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

                TextColumn::make('question')
                    ->label('❓ Question')
                    ->limit(80)
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn ($record) => $record->question),

                TextColumn::make('source')
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

                TextColumn::make('difficulty_level')
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

                IconColumn::make('was_answered')
                    ->label('✅ Answered')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor(Color::Green)
                    ->falseColor(Color::Gray)
                    ->tooltip(fn ($record) => $record->was_answered ? 'Question was answered' : 'Question not answered'),

                TextColumn::make('related_topics')
                    ->label('🏷️ Topics')
                    ->badge()
                    ->separator(',')
                    ->color(Color::Gray)
                    ->toggleable()
                    ->tooltip('Related topics'),

                TextColumn::make('created_at')
                    ->label('📅 Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Date question was recorded'),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('category')
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

                SelectFilter::make('source')
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

                SelectFilter::make('difficulty_level')
                    ->label('Difficulty Level')
                    ->options([
                        'basic' => '🟢 Basic',
                        'intermediate' => '🟡 Intermediate',
                        'advanced' => '🔴 Advanced',
                        'complex' => '🚨 Complex/Difficult',
                    ]),

                TernaryFilter::make('was_answered')
                    ->label('Answer Status')
                    ->placeholder('All questions')
                    ->trueLabel('Answered')
                    ->falseLabel('Not answered'),

                TernaryFilter::make('needs_followup')
                    ->label('Needs Follow-up')
                    ->placeholder('All questions')
                    ->trueLabel('Needs follow-up')
                    ->falseLabel('No follow-up needed')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('follow_up_needed'),
                        false: fn ($query) => $query->whereNull('follow_up_needed'),
                    ),

                Filter::make('created_at')
                    ->label('Date Added')
                    ->schema([
                        DatePicker::make('created_from')
                            ->native(false)
                            ->label('From'),
                        DatePicker::make('created_until')
                            ->native(false)
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
                            $indicators[] = 'From: '.Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until: '.Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                CreateAction::make()
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
            ->recordActions([
                Action::make('mark_answered')
                    ->label('Mark Answered')
                    ->icon('heroicon-o-check-circle')
                    ->color(Color::Green)
                    ->schema([
                        Textarea::make('answer')
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

                ViewAction::make()
                    ->color(Color::Gray),

                EditAction::make()
                    ->color(Color::Orange)
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Question updated')
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->color(Color::Red),

                    BulkAction::make('mark_as_answered')
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

                    BulkAction::make('assign_category')
                        ->label('Assign Category')
                        ->icon('heroicon-o-tag')
                        ->color(Color::Blue)
                        ->form([
                            Select::make('category')
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

                    BulkAction::make('export_questions')
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

                    RestoreBulkAction::make()
                        ->color(Color::Green),

                    ForceDeleteBulkAction::make()
                        ->color(Color::Red),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
