<?php

namespace App\Filament\Resources\MissionResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DebriefNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'debriefNotes';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $title = '📋 Debrief Notes';

    protected static ?string $label = 'Debrief Note';

    protected static ?string $pluralLabel = 'Debrief Notes';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->debriefNotes()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('📝 Debrief Note')
                    ->description('Record important observations, learnings, and feedback from the mission')
                    ->schema([
                        Select::make('category')
                            ->label('📂 Category')
                            ->helperText('Classify the type of note for better organization')
                            ->options([
                                'general' => '📋 General Observations',
                                'challenges' => '⚠️ Challenges Faced',
                                'successes' => '✅ Successes & Wins',
                                'improvements' => '🔄 Areas for Improvement',
                                'logistics' => '📦 Logistics & Operations',
                                'team' => '👥 Team Performance',
                                'students' => '🎓 Student Engagement',
                                'feedback' => '💬 Feedback & Suggestions',
                            ])
                            ->native(false)
                            ->columnSpan(1),

                        Select::make('priority')
                            ->label('⚡ Priority')
                            ->helperText('How important is this observation?')
                            ->options([
                                'low' => '🟢 Low',
                                'medium' => '🟡 Medium',
                                'high' => '🔴 High',
                                'critical' => '🚨 Critical',
                            ])
                            ->default('medium')
                            ->native(false)
                            ->columnSpan(1),

                        Textarea::make('note')
                            ->label('📄 Note Content')
                            ->helperText('Detailed notes about the mission experience, challenges, successes, and lessons learned')
                            ->required()
                            ->rows(8)
                            ->placeholder('Enter detailed debrief notes here...')
                            ->columnSpanFull(),

                        TagsInput::make('tags')
                            ->label('🏷️ Tags')
                            ->helperText('Add keywords for easy searching')
                            ->placeholder('Add tags...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('note')
            ->columns([
                TextColumn::make('category')
                    ->label('📂 Category')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'general' => '📋 General',
                        'challenges' => '⚠️ Challenges',
                        'successes' => '✅ Successes',
                        'improvements' => '🔄 Improvements',
                        'logistics' => '📦 Logistics',
                        'team' => '👥 Team',
                        'students' => '🎓 Students',
                        'feedback' => '💬 Feedback',
                        default => '📋 General',
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'challenges' => Color::Red,
                        'successes' => Color::Green,
                        'improvements' => Color::Yellow,
                        'logistics' => Color::Blue,
                        'team' => Color::Purple,
                        'students' => Color::Cyan,
                        'feedback' => Color::Orange,
                        default => Color::Gray,
                    })
                    ->sortable()
                    ->tooltip('Note category'),

                TextColumn::make('priority')
                    ->label('⚡')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'low' => '🟢',
                        'medium' => '🟡',
                        'high' => '🔴',
                        'critical' => '🚨',
                        default => '🟡',
                    })
                    ->tooltip(fn ($state) => ucfirst($state ?? 'medium').' priority'),

                TextColumn::make('note')
                    ->label('📝 Note')
                    ->limit(80)
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn ($record) => $record->note),

                TextColumn::make('tags')
                    ->label('🏷️ Tags')
                    ->badge()
                    ->separator(',')
                    ->color(Color::Gray)
                    ->toggleable()
                    ->placeholder('No tags'),

                TextColumn::make('created_at')
                    ->label('📅 Added')
                    ->dateTime('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->since()
                    ->tooltip('Date note was added'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('📂 Category')
                    ->options([
                        'general' => '📋 General Observations',
                        'challenges' => '⚠️ Challenges Faced',
                        'successes' => '✅ Successes & Wins',
                        'improvements' => '🔄 Areas for Improvement',
                        'logistics' => '📦 Logistics & Operations',
                        'team' => '👥 Team Performance',
                        'students' => '🎓 Student Engagement',
                        'feedback' => '💬 Feedback & Suggestions',
                    ])
                    ->multiple(),

                SelectFilter::make('priority')
                    ->label('⚡ Priority')
                    ->options([
                        'low' => '🟢 Low',
                        'medium' => '🟡 Medium',
                        'high' => '🔴 High',
                        'critical' => '🚨 Critical',
                    ])
                    ->multiple(),

                Filter::make('created_at')
                    ->label('📅 Date Added')
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
                    ->label('Add Note')
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Debrief note added')
                            ->body('New debrief note has been recorded.')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('mark_high_priority')
                        ->label('Mark High Priority')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color(Color::Red)
                        ->action(function ($record) {
                            $record->update(['priority' => 'high']);
                            Notification::make()
                                ->title('Marked as high priority')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => ($record->priority ?? 'medium') !== 'high' && ($record->priority ?? 'medium') !== 'critical'),

                    ViewAction::make()
                        ->color(Color::Gray),

                    EditAction::make()
                        ->color(Color::Orange)
                        ->after(function ($record) {
                            Notification::make()
                                ->title('Note updated')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()
                        ->color(Color::Red),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('assign_category')
                        ->label('Set Category')
                        ->icon('heroicon-o-tag')
                        ->color(Color::Blue)
                        ->form([
                            Select::make('category')
                                ->label('Category')
                                ->options([
                                    'general' => '📋 General Observations',
                                    'challenges' => '⚠️ Challenges Faced',
                                    'successes' => '✅ Successes & Wins',
                                    'improvements' => '🔄 Areas for Improvement',
                                    'logistics' => '📦 Logistics & Operations',
                                    'team' => '👥 Team Performance',
                                    'students' => '🎓 Student Engagement',
                                    'feedback' => '💬 Feedback & Suggestions',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $record->update(['category' => $data['category']]);
                            });

                            Notification::make()
                                ->title('Category assigned')
                                ->body('Category has been assigned to '.count($records).' notes.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('set_priority')
                        ->label('Set Priority')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color(Color::Orange)
                        ->form([
                            Select::make('priority')
                                ->label('Priority')
                                ->options([
                                    'low' => '🟢 Low',
                                    'medium' => '🟡 Medium',
                                    'high' => '🔴 High',
                                    'critical' => '🚨 Critical',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $record->update(['priority' => $data['priority']]);
                            });

                            Notification::make()
                                ->title('Priority updated')
                                ->body('Priority has been set for '.count($records).' notes.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->color(Color::Red),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    protected function canCreate(): bool
    {
        return userCan('create debrief note');
    }
}
