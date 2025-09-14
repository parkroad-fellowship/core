<?php

namespace App\Filament\Resources\SpeakerResource\RelationManagers;

use App\Enums\PRFEventType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

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
                        Forms\Components\Section::make('Event Details')
                            ->description('Basic event information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Event Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Enter a descriptive name for this event')
                                    ->placeholder('e.g., Annual Conference, Prayer Meeting'),

                                Forms\Components\Select::make('responsible_desk')
                                    ->label('🏢 Responsible Desk')
                                    ->options(\App\Enums\PRFResponsibleDesk::getOptions())
                                    ->required()
                                    ->placeholder('Select desk...')
                                    ->helperText('The desk handling this event'),

                                Forms\Components\Select::make('event_type')
                                    ->label('Event Type')
                                    ->required()
                                    ->options(PRFEventType::getOptions())
                                    ->helperText('Set the type of this event.'),

                                Forms\Components\Textarea::make('description')
                                    ->label('Event Description')
                                    ->required()
                                    ->rows(4)
                                    ->helperText('Provide a detailed description of the event')
                                    ->placeholder('Describe what this event is about, its purpose, and what attendees can expect...')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),

                        Forms\Components\Section::make('Date & Time')
                            ->description('Event schedule and timing')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->native(false)
                                    ->timezone(Auth::user()->timezone)
                                    ->after(today())
                                    ->required()
                                    ->helperText('Select the event start date')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Auto-set end_date if not already set
                                        if ($state) {
                                            $set('end_date', $state);
                                        }
                                    }),

                                Forms\Components\TimePicker::make('start_time')
                                    ->label('Start Time')
                                    ->seconds(false)
                                    ->native(false)
                                    ->required()
                                    ->default('08:00')
                                    ->helperText('Select the event start time'),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->native(false)
                                    ->timezone(Auth::user()->timezone)
                                    ->afterOrEqual('start_date')
                                    ->required()

                                    ->helperText('Select the event end date'),

                                Forms\Components\TimePicker::make('end_time')
                                    ->label('End Time')
                                    ->seconds(false)
                                    ->native(false)
                                    ->required()
                                    ->default('17:00')
                                    ->helperText('Select the event end time'),
                            ])
                            ->columns(2),

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
