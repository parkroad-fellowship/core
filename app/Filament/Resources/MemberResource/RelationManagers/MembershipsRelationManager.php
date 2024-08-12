<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use App\Enums\PRFMembershipType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MembershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'memberships';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('spiritual_year_id')
                    ->relationship(
                        name: 'spiritualYear',
                        titleAttribute: 'name',
                    )
                    ->required(),
                Forms\Components\Select::make('type')
                    ->required()
                    ->options(PRFMembershipType::getOptions())
                    ->default(PRFMembershipType::FRIEND),
                Forms\Components\Checkbox::make('approved'),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('member_id')
            ->columns([
                Tables\Columns\TextColumn::make('spiritualYear.name'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($record) => PRFMembershipType::fromValue($record->type)->name)
                    ->sortable(),
                Tables\Columns\IconColumn::make('approved')
                    ->boolean(),
                Tables\Columns\TextColumn::make('amount'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['amount'] = match ($data['type']) {
                            PRFMembershipType::FRIEND => 0,
                            PRFMembershipType::YEARLY_MEMBER => 500,
                            PRFMembershipType::LIFETIME_MEMBER => 5000,
                        };

                        return $data;
                    }),
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
