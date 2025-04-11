<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use App\Enums\PRFActiveStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CourseGroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'courseGroups';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('group_id')
                    ->relationship(
                        name: 'group',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                    )
                    ->label('Group')
                    ->required()
                    ->searchable(),
                Forms\Components\DatePicker::make('start_date')
                    ->native(false)
                    ->label('Start Date')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('group_id')
            ->columns([
                Tables\Columns\TextColumn::make('group.name'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date'),
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
                    Tables\Actions\DetachBulkAction::make(),
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
