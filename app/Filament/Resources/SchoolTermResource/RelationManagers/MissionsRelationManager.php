<?php

namespace App\Filament\Resources\SchoolTermResource\RelationManagers;

use App\Enums\PRFActiveStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'missions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('school_id')
                    ->required()
                    ->relationship('school', 'name'),
                Forms\Components\Select::make('mission_type_id')
                    ->required()
                    ->relationship('missionType', 'name'),
                Forms\Components\Datepicker::make('start_date')
                    ->required(),
                Forms\Components\Datepicker::make('end_date'),
                Forms\Components\Textarea::make('mission_prep_notes')
                    ->columnSpanFull(),
                Forms\Components\Select::make('is_active')
                    ->required()
                    ->options(PRFActiveStatus::getOptions())
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->hiddenOn('create'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('school_id')
            ->columns([
                Tables\Columns\TextColumn::make('school.name'),
                Tables\Columns\TextColumn::make('missionType.name'),
                Tables\Columns\TextColumn::make('start_date')
                ->date(),
                Tables\Columns\TextColumn::make('end_date')
                ->date(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
