<?php

namespace App\Filament\Resources\Lessons\RelationManagers;

use App\Enums\PRFCompletionStatus;
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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class LessonMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'lessonMembers';

    protected static ?string $title = 'Member Progress';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('member.first_name')
                    ->relationship('member', 'full_name')
                    ->disabled()
                    ->required(),
                Select::make('completion_status')
                    ->options(PRFCompletionStatus::getOptions())
                    ->disabled()
                    ->required(),
                DateTimePicker::make('completed_at')
                    ->seconds(false)
                    ->disabled()
                    ->label('Completed On')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('member_id')
            ->columns([
                TextColumn::make('member.first_name')->wrap(),
                TextColumn::make('completion_status')
                    ->label('Completion Status')
                    ->formatStateUsing(fn ($record) => PRFCompletionStatus::fromValue($record->completion_status)->name)
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Completed On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
