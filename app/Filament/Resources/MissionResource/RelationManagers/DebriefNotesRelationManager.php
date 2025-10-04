<?php

namespace App\Filament\Resources\MissionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DebriefNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'debriefNotes';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $label = 'Debrief Note';

    protected static ?string $pluralLabel = 'Debrief Notes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('📝 Debrief Note')
                    ->description('Record important observations, learnings, and feedback from the mission')
                    ->schema([
                        Forms\Components\Textarea::make('note')
                            ->label('Debrief Note')
                            ->helperText('Detailed notes about the mission experience, challenges, successes, and lessons learned')
                            ->required()
                            ->rows(12)
                            ->placeholder('Enter detailed debrief notes...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('note')
            ->columns([
                Tables\Columns\TextColumn::make('note')
                    ->label('📝 Note Content')
                    ->limit(100)
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn ($record) => $record->note),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Date note was added'),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->label('Date Added')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->native(false)
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
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
                            ->title('Debrief note added')
                            ->body('New debrief note has been recorded.')
                            ->success()
                            ->send();
                    }),

            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color(Color::Gray),

                Tables\Actions\EditAction::make()
                    ->color(Color::Orange)
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Note updated')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->color(Color::Red),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\BulkAction::make('assign_category')
                        ->label('Assign Category')
                        ->icon('heroicon-o-tag')
                        ->color(Color::Blue)
                        ->form([
                            Forms\Components\Select::make('category')
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
                        }),

                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function canCreate(): bool
    {
        return userCan('create debrief note');
    }
}
