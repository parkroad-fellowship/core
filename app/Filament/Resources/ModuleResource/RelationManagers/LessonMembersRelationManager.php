<?php

namespace App\Filament\Resources\ModuleResource\RelationManagers;

use App\Enums\PRFCompletionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LessonMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'mmemberModules';

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
                            ->disabled()
                            ->preload()
                            ->helperText('👤 Select the member to track progress for'),

                        Forms\Components\Select::make('completion_status')
                            ->label('Completion Status')
                            ->options(PRFCompletionStatus::getOptions())
                            ->required()
                            ->disabled()
                            ->helperText('📈 Current completion status'),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completed On')
                            ->seconds(false)
                            ->disabled()
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
            ->heading('📚 Module Member Progress')
            ->description('Track and manage member progress through this module')
            ->columns([
                Tables\Columns\TextColumn::make('member.full_name')
                    ->label('Member Name')
                    ->description(fn ($record) => $record->member?->email ?? 'No email')
                    ->searchable(['member.first_name', 'member.last_name'])
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('completion_status')
                    ->label('Status')
                    ->formatStateUsing(fn ($record) => PRFCompletionStatus::fromValue($record->completion_status)->getLabel())
                    ->badge()
                    ->color(fn ($record) => match ($record->completion_status) {
                        PRFCompletionStatus::COMPLETE->value => 'success',
                        PRFCompletionStatus::INCOMPLETE->value => 'warning',
                        default => 'gray'
                    })
                    ->icon(fn ($record) => match ($record->completion_status) {
                        PRFCompletionStatus::COMPLETE->value => 'heroicon-o-check-circle',
                        PRFCompletionStatus::INCOMPLETE->value => 'heroicon-o-clock',
                        default => 'heroicon-o-question-mark-circle'
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed On')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->placeholder('Not completed')
                    ->description('Date & time of completion')
                    ->icon('heroicon-o-calendar-days'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Enrolled')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description('Enrollment date'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description('Last progress update'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('completion_status')
                    ->label('Status')
                    ->options([
                        PRFCompletionStatus::INCOMPLETE->value => 'Incomplete',
                        PRFCompletionStatus::COMPLETE->value => 'Complete',
                    ])
                    ->multiple()
                    ->placeholder('All statuses'),

                Tables\Filters\Filter::make('completed_this_month')
                    ->label('Completed This Month')
                    ->query(fn (Builder $query) => $query->whereMonth('completed_at', now()->month))
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Member')
                    ->icon('heroicon-o-plus')
                    ->successNotificationTitle('Member added successfully!')
                    ->modalHeading('Add Member to Module')
                    ->modalDescription('Enroll a new member in this module to track their progress'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View Details')
                        ->icon('heroicon-o-eye'),

                    Tables\Actions\EditAction::make()
                        ->label('Update Progress')
                        ->icon('heroicon-o-pencil-square')
                        ->successNotificationTitle('Progress updated successfully!'),

                    Tables\Actions\DeleteAction::make()
                        ->label('Remove')
                        ->icon('heroicon-o-trash')
                        ->successNotificationTitle('Member removed from module'),
                ])
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Remove Selected')
                        ->successNotificationTitle('Selected members removed from module'),

                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ])
                    ->label('Bulk Actions'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
