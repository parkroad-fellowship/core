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
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

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
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $member = \App\Models\Member::find($state);
                            if ($member) {
                                $set('phone_display', $member->phone_number);
                            }
                        } else {
                            $set('phone_display', null);
                        }
                    }),
                Forms\Components\TextInput::make('phone_display')
                    ->label('Phone Number')
                    ->disabled()
                    ->dehydrated(false)
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if ($record && $record->member) {
                            $component->state($record->member->phone_number);
                        }
                    }),
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
                Tables\Columns\TextColumn::make('member.full_name')
                    ->searchable()
                    ->sortable()
                    ->label('Name'),
                Tables\Columns\TextColumn::make('member.gender')
                    ->formatStateUsing(fn ($record) => PRFGender::fromValue($record->member->gender)->name)
                    ->label('Gender'),
                PhoneColumn::make('member.phone_number')
                    ->label('Phone'),
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
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options(PRFMissionSubscriptionStatus::getOptions())
                    ->default([
                        PRFMissionSubscriptionStatus::PENDING->value,
                        PRFMissionSubscriptionStatus::APPROVED->value,
                        PRFMissionSubscriptionStatus::CONFLICT->value,
                    ])
                    ->label('Status'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('view_member')
                    ->label('View member')
                    ->color('primary')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.members.view', ['record' => $record->member_id]))
                    ->openUrlInNewTab(),
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
