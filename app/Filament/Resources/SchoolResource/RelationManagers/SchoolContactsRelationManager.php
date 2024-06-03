<?php

namespace App\Filament\Resources\SchoolResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SchoolContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'schoolContacts';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('contact_type_id')
                    ->relationship('contactType', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->required()
                    ->tel(),
                Forms\Components\TextInput::make('email')
                    ->email(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('contactType.name')
                    ->label('Type'),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('email'),

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
