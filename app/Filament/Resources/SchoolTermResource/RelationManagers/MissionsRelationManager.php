<?php

namespace App\Filament\Resources\SchoolTermResource\RelationManagers;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFMissionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'missions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('school_id')
                    ->required()
                    ->relationship('school', 'name')
                    ->searchable(),
                Forms\Components\Select::make('mission_type_id')
                    ->required()
                    ->relationship(
                        name: 'missionType',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                    ),
                Forms\Components\DatePicker::make('start_date')
                    ->timezone(Auth::user()->timezone)
                    ->native(false)
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->timezone(Auth::user()->timezone)
                    ->native(false),
                Forms\Components\Textarea::make('mission_prep_notes')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options(PRFMissionStatus::getOptions())
                    ->default(PRFMissionStatus::PENDING->value),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('school.name')
            ->columns([
                Tables\Columns\TextColumn::make('school.name'),
                Tables\Columns\TextColumn::make('missionType.name')
                    ->wrap(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->timezone(Auth::user()->timezone),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->timezone(Auth::user()->timezone),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
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

    protected function canCreate(): bool
    {
        return userCan('create mission');
    }
}
