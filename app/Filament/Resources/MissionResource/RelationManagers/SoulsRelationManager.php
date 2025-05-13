<?php

namespace App\Filament\Resources\MissionResource\RelationManagers;

use App\Enums\PRFActiveStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SoulsRelationManager extends RelationManager
{
    protected static string $relationship = 'souls';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('class_group_id')
                    ->relationship(
                        name: 'classGroup',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                    )
                    ->required(),
                Forms\Components\TextInput::make('full_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('admission_number')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                Tables\Columns\TextColumn::make('admission_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('classGroup.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(),
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
        return userCan('create soul');
    }
}
