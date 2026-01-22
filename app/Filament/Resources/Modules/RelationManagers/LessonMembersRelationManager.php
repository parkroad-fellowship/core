<?php

namespace App\Filament\Resources\Modules\RelationManagers;

use App\Enums\PRFCompletionStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LessonMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'mmemberModules';

    protected static ?string $title = 'Member Progress';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-academic-cap';

    protected static ?string $description = 'Track member progress and completion status';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Member Progress Information')
                    ->description('Track and update member learning progress')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Select::make('member_id')
                            ->label('Member')
                            ->relationship('member', 'full_name')
                            ->required()
                            ->searchable()
                            ->disabled()
                            ->preload()
                            ->helperText('👤 Select the member to track progress for'),

                        Select::make('completion_status')
                            ->label('Completion Status')
                            ->options(PRFCompletionStatus::getOptions())
                            ->required()
                            ->disabled()
                            ->helperText('📈 Current completion status'),

                        DateTimePicker::make('completed_at')
                            ->label('Completed On')
                            ->seconds(false)
                            ->disabled()
                            ->native(false)
                            ->helperText('📅 Date and time when completed (if applicable)')
                            ->visible(fn (Get $get) => $get('completion_status') === PRFCompletionStatus::COMPLETE->value),
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
                TextColumn::make('member.full_name')
                    ->label('Member Name')
                    ->description(fn ($record) => $record->member?->email ?? 'No email')
                    ->searchable(['member.first_name', 'member.last_name'])
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->icon('heroicon-o-user'),

                TextColumn::make('completion_status')
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

                TextColumn::make('completed_at')
                    ->label('Completed On')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->placeholder('Not completed')
                    ->description('Date & time of completion')
                    ->icon('heroicon-o-calendar-days'),

                TextColumn::make('created_at')
                    ->label('Enrolled')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description('Enrollment date'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description('Last progress update'),
            ])
            ->filters([
                SelectFilter::make('completion_status')
                    ->label('Status')
                    ->options([
                        PRFCompletionStatus::INCOMPLETE->value => 'Incomplete',
                        PRFCompletionStatus::COMPLETE->value => 'Complete',
                    ])
                    ->multiple()
                    ->placeholder('All statuses'),

                Filter::make('completed_this_month')
                    ->label('Completed This Month')
                    ->query(fn (Builder $query) => $query->whereMonth('completed_at', now()->month))
                    ->toggle(),

                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Member')
                    ->icon('heroicon-o-plus')
                    ->successNotificationTitle('Member added successfully!')
                    ->modalHeading('Add Member to Module')
                    ->modalDescription('Enroll a new member in this module to track their progress'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('View Details')
                        ->icon('heroicon-o-eye'),

                    EditAction::make()
                        ->label('Update Progress')
                        ->icon('heroicon-o-pencil-square')
                        ->successNotificationTitle('Progress updated successfully!'),

                    DeleteAction::make()
                        ->label('Remove')
                        ->icon('heroicon-o-trash')
                        ->successNotificationTitle('Member removed from module'),
                ])
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([

                    DeleteBulkAction::make()
                        ->label('Remove Selected')
                        ->successNotificationTitle('Selected members removed from module'),

                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
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
