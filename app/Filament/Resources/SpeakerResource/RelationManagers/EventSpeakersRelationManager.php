<?php

namespace App\Filament\Resources\SpeakerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EventSpeakersRelationManager extends RelationManager
{
    protected static string $relationship = 'eventSpeakers';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('prf_event_id')
                    ->label('Event')
                    ->relationship('event', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535),
                    ])
                    ->createOptionUsing(function (array $data) {
                        return \App\Models\PRFEvent::create($data)->getKey();
                    }),
                Forms\Components\TextInput::make('topic')
                    ->label('Speaking Topic')
                    ->required()
                    ->maxLength(255)
                    ->hint('What will this speaker talk about?'),
                Forms\Components\Textarea::make('description')
                    ->label('Topic Description')
                    ->rows(3)
                    ->maxLength(65535)
                    ->hint('Detailed description of the speaking topic'),
                Forms\Components\Textarea::make('comments')
                    ->label('Internal Comments')
                    ->rows(3)
                    ->maxLength(65535)
                    ->hint('Private notes about this speaking engagement'),
            ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('topic')
            ->columns([
                Tables\Columns\TextColumn::make('event.name')
                    ->label('Event')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-m-calendar-days'),
                Tables\Columns\TextColumn::make('topic')
                    ->label('Speaking Topic')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->wrap(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('event.start_date')
                    ->label('Event Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('comments')
                    ->label('Has Notes')
                    ->boolean()
                    ->trueIcon('heroicon-o-chat-bubble-left-right')
                    ->falseIcon('heroicon-o-minus')
                    ->state(fn ($record) => ! empty($record->comments)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('event.start_date', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Speaking Engagement')
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading('Add Speaking Engagement')
                    ->modalDescription('Add this speaker to an event with a specific topic')
                    ->successNotificationTitle('Speaking engagement added successfully'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading(fn ($record) => "Speaking Engagement: {$record->topic}"),
                    Tables\Actions\EditAction::make()
                        ->successNotificationTitle('Speaking engagement updated'),
                    Tables\Actions\DeleteAction::make()
                        ->successNotificationTitle('Speaking engagement removed'),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])->label('Actions')
                    ->color('primary')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->button(),
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
