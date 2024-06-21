<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use App\Enums\PRFCompletionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CourseMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'courseMembers';

    protected static ?string $title = 'Courses';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('course.name')
                    ->relationship('course', 'name')
                    ->required(),
                Forms\Components\TextInput::make('percent_complete')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('completion_status')
                    ->options(PRFCompletionStatus::getOptions())
                    ->required(),
                Forms\Components\DateTimePicker::make('completed_at')
                    ->label('Completed On')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('course_id')
            ->columns([
                Tables\Columns\TextColumn::make('course.name')->wrap(),
                Tables\Columns\TextColumn::make('percent_complete')
                    ->label('Percent Complete'),
                Tables\Columns\TextColumn::make('completion_status')
                    ->label('Completion Status')
                    ->formatStateUsing(fn ($record) => PRFCompletionStatus::fromValue($record->completion_status)->name)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
