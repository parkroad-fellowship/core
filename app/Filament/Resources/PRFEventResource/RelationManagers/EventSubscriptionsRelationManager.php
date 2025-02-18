<?php

namespace App\Filament\Resources\PRFEventResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EventSubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'eventSubscriptions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('member_id')
                    ->required()
                    ->relationship('member', 'first_name'),
                Forms\Components\TextInput::make('number_of_attendees')
                    ->integer()
                    ->maxValue(6)
                    ->default(1),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('member_id')
            ->columns([
                Tables\Columns\TextColumn::make('member.first_name')
                    ->label('First Name'),
                Tables\Columns\TextColumn::make('member.last_name')
                    ->label('Last Name'),
                Tables\Columns\TextColumn::make('number_of_attendees')
                    ->label('Number of Attendees'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime()
                    ->sortable(),
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
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
