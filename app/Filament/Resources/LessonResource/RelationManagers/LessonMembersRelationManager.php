<?php

namespace App\Filament\Resources\LessonResource\RelationManagers;

use App\Enums\PRFCompletionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class LessonMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'lessonMembers';

    protected static ?string $title = 'Member Progress';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('member.first_name')
                    ->relationship('member', 'full_name')
                    ->required(),
                Forms\Components\Select::make('completion_status')
                    ->options(PRFCompletionStatus::getOptions())
                    ->required(),
                Forms\Components\DateTimePicker::make('completed_at')
                    ->seconds(false)
                    ->label('Completed On')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('member_id')
            ->columns([
                Tables\Columns\TextColumn::make('member.first_name')->wrap(),
                Tables\Columns\TextColumn::make('completion_status')
                    ->label('Completion Status')
                    ->formatStateUsing(fn ($record) => PRFCompletionStatus::fromValue($record->completion_status)->name)
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
