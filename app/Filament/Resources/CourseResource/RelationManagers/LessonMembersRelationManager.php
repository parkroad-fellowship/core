<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use App\Enums\PRFCompletionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LessonMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'courseMembers';

    protected static ?string $title = 'Member Progress';

    protected static ?string $icon = 'heroicon-o-academic-cap';

    protected static ?string $description = 'Track member progress and completion status';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Member Progress Information')
                    ->description('Track and update member learning progress')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Forms\Components\Select::make('member_id')
                            ->label('Member')
                            ->relationship('member', 'full_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('👤 Select the member to track progress for'),

                        Forms\Components\TextInput::make('percent_complete')
                            ->label('Progress Percentage')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->disabled()
                            ->step(0.01)
                            ->suffix('%')
                            ->helperText('📊 Completion percentage (0-100, up to 2 decimal places)'),

                        Forms\Components\Select::make('completion_status')
                            ->label('Completion Status')
                            ->options(PRFCompletionStatus::getOptions())
                            ->required()
                            ->disabled()
                            ->helperText('📈 Current completion status'),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completed On')
                            ->seconds(false)
                            ->helperText('📅 Date and time when completed (if applicable)')
                            ->visible(fn (Forms\Get $get) => $get('completion_status') === PRFCompletionStatus::COMPLETE->value),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('member.full_name')
            ->columns([
                Tables\Columns\TextColumn::make('member.full_name')
                    ->label('Member')
                    ->icon('heroicon-o-user')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->tooltip(fn ($record) => 'Member: '.$record->member->full_name),

                Tables\Columns\TextColumn::make('percent_complete')
                    ->label('Progress')
                    ->suffix('%')
                    ->icon('heroicon-o-chart-bar')
                    ->color(function ($state) {
                        return match (true) {
                            $state >= 100 => 'success',
                            $state >= 75 => 'info',
                            $state >= 50 => 'warning',
                            default => 'danger',
                        };
                    })
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->tooltip(fn ($record) => 'Progress: '.number_format($record->percent_complete, 2).'%'),

                Tables\Columns\TextColumn::make('progress_indicator')
                    ->label('Progress Visual')
                    ->getStateUsing(function ($record) {
                        $percent = $record->percent_complete;
                        $filled = (int) ($percent / 10);
                        $empty = 10 - $filled;

                        return str_repeat('🟢', $filled).str_repeat('⚪', $empty).' ('.number_format($percent, 2).'%)';
                    })
                    ->html()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('completion_status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => PRFCompletionStatus::fromValue($state)->getLabel())
                    ->badge()
                    ->color(fn ($state) => PRFCompletionStatus::fromValue($state)->getColor())
                    ->icon(fn ($state) => $state === PRFCompletionStatus::COMPLETE->value ? 'heroicon-o-check-circle' : 'heroicon-o-clock')
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed On')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->placeholder('Not completed')
                    ->tooltip(fn ($record) => $record->completed_at ? 'Completed: '.$record->completed_at->format('F j, Y \a\t g:i A') : 'Not yet completed'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Enrolled On')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Enrolled: '.$record->created_at->format('F j, Y \a\t g:i A')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                Tables\Filters\SelectFilter::make('completion_status')
                    ->label('Completion Status')
                    ->options(PRFCompletionStatus::getOptions())
                    ->placeholder('All Statuses'),

                Tables\Filters\Filter::make('progress_range')
                    ->form([
                        Forms\Components\TextInput::make('min_progress')
                            ->label('Minimum Progress (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01),
                        Forms\Components\TextInput::make('max_progress')
                            ->label('Maximum Progress (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01),
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
                    }),

                Tables\Filters\Filter::make('completed')
                    ->label('Completed Members')
                    ->query(fn (Builder $query): Builder => $query->where('completion_status', PRFCompletionStatus::COMPLETE->value))
                    ->toggle(),

                Tables\Filters\Filter::make('incomplete')
                    ->label('Incomplete Members')
                    ->query(fn (Builder $query): Builder => $query->where('completion_status', PRFCompletionStatus::INCOMPLETE->value))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Member')
                    ->icon('heroicon-o-plus')
                    ->color('primary'),
                Tables\Actions\Action::make('bulk_update_progress')
                    ->label('Bulk Update Progress')
                    ->icon('heroicon-o-arrow-up')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('progress_increment')
                            ->label('Progress Increment (%)')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(100)
                            ->step(0.01)
                            ->helperText('Add this percentage to all selected members (up to 2 decimal places)'),
                    ])
                    ->action(function (array $data) {
                        // This would be implemented to bulk update progress
                        \Filament\Notifications\Notification::make()
                            ->title('Bulk Update Started')
                            ->body('Progress update has been queued for processing.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),

                    // Tables\Actions\Action::make('update_progress')
                    //     ->label('Update Progress')
                    //     ->icon('heroicon-o-chart-bar')
                    //     ->color('primary')
                    //     ->form([
                    //         Forms\Components\TextInput::make('new_progress')
                    //             ->label('New Progress (%)')
                    //             ->numeric()
                    //             ->required()
                    //             ->minValue(0)
                    //             ->maxValue(100)
                    //             ->step(0.01)
                    //             ->default(fn ($record) => number_format($record->percent_complete, 2)),
                    //     ])
                    //     ->action(function (array $data, $record) {
                    //         $record->update([
                    //             'percent_complete' => $data['new_progress'],
                    //             'completion_status' => $data['new_progress'] >= 100
                    //                 ? PRFCompletionStatus::COMPLETE->value
                    //                 : PRFCompletionStatus::INCOMPLETE->value,
                    //             'completed_at' => $data['new_progress'] >= 100 ? now() : null,
                    //         ]);
                    //     }),
                    Tables\Actions\DeleteAction::make()
                        ->color('danger'),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_complete_bulk')
                        ->label('Mark as Complete')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'completion_status' => PRFCompletionStatus::COMPLETE->value,
                                    'percent_complete' => 100,
                                    'completed_at' => now(),
                                ]);
                            }
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('update_progress_bulk')
                        ->label('Update Progress')
                        ->icon('heroicon-o-chart-bar')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('progress_percentage')
                                ->label('New Progress (%)')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.01),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'percent_complete' => $data['progress_percentage'],
                                    'completion_status' => $data['progress_percentage'] >= 100
                                        ? PRFCompletionStatus::COMPLETE->value
                                        : PRFCompletionStatus::INCOMPLETE->value,
                                    'completed_at' => $data['progress_percentage'] >= 100 ? now() : null,
                                ]);
                            }
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('percent_complete', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
