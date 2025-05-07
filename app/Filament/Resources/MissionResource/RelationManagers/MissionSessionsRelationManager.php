<?php

namespace App\Filament\Resources\MissionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MissionSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'missionSessions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('facilitator_id')
                    ->relationship('facilitator', 'full_name')
                    ->required(),
                Forms\Components\Select::make('speaker_id')
                    ->relationship('speaker', 'full_name'),
                Forms\Components\Select::make('class_group_id')
                    ->relationship('classGroup', 'name'),
                Forms\Components\DateTimePicker::make('starts_at')
                    ->native(false)
                    ->required(),
                Forms\Components\DateTimePicker::make('ends_at')
                    ->native(false)
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('facilitator_id')
            ->columns([
                Tables\Columns\TextColumn::make('facilitator.full_name')
                    ->label('Facilitator'),
                Tables\Columns\TextColumn::make('speaker.full_name')
                    ->label('Speaker'),
                Tables\Columns\TextColumn::make('classGroup.name')
                    ->label('Class Group'),
                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->label('Starts At'),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Ends At')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone),
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
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
