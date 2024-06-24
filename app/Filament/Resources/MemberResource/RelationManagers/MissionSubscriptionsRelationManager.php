<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use App\Enums\PRFMissionRole;
use App\Enums\PRFMissionSubscriptionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MissionSubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'missionSubscriptions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('mission_id')
                    ->required()
                    ->label('School')
                    ->relationship('mission.school', 'name')
                    ->searchable(),
                Forms\Components\Select::make('mission_role')
                    ->required()
                    ->options(PRFMissionRole::getOptions())
                    ->default(PRFMissionRole::MEMBER->value),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options(PRFMissionSubscriptionStatus::getOptions())
                    ->default(PRFMissionSubscriptionStatus::PENDING->value)
                    ->hiddenOn(['create']),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('')
            ->columns([
                Tables\Columns\TextColumn::make('mission.school.name'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($record) => PRFMissionSubscriptionStatus::fromValue($record->status)->name)
                    ->sortable(),
                Tables\Columns\TextColumn::make('mission_role')
                    ->label('Role')
                    ->formatStateUsing(fn ($record) => PRFMissionRole::fromValue($record->mission_role)->name)
                    ->sortable(),
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

    protected function canCreate(): bool
    {
        return auth()->user()->can('create mission subscription');
    }
}
