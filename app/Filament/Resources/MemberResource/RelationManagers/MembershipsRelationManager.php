<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use App\Enums\PRFMembershipType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MembershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'memberships';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('year')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->required()
                    ->options(PRFMembershipType::getOptions())
                    ->default(PRFMembershipType::FRIEND->value),
                Forms\Components\Checkbox::make('approved'),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('member_id')
            ->columns([
                Tables\Columns\TextColumn::make('year'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Status')
                    ->formatStateUsing(fn($record) => PRFMembershipType::fromValue($record->type)->name)
                    ->sortable(),
                Tables\Columns\IconColumn::make('approved')
                    ->boolean(),
                Tables\Columns\TextColumn::make('amount'),
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
