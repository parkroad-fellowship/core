<?php

namespace App\Filament\Resources\MissionResource\RelationManagers;

use App\Enums\PRFGender;
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
                Forms\Components\Select::make('member_id')
                    ->required()
                    ->relationship('member', 'full_name')
                    ->searchable(),
                Forms\Components\Select::make('mission_role')
                    ->required()
                    ->options(PRFMissionRole::getOptions())
                    ->default(PRFMissionRole::MEMBER->value),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options(PRFMissionSubscriptionStatus::getOptions())
                    ->default(PRFMissionSubscriptionStatus::PENDING->value),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('member.name')
            ->columns([
                Tables\Columns\TextColumn::make('member.first_name')
                    ->searchable()
                    ->sortable()
                    ->label('First Name'),
                Tables\Columns\TextColumn::make('member.last_name')
                    ->searchable()
                    ->sortable()
                    ->label('Last Name'),
                Tables\Columns\TextColumn::make('member.gender')
                    ->formatStateUsing(fn ($record) => PRFGender::fromValue($record->member->gender)->name)
                    ->label('Gender'),
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
        return userCan('create mission subscription');
    }
}
