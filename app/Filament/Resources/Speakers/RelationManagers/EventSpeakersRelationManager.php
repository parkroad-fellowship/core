<?php

namespace App\Filament\Resources\Speakers\RelationManagers;

use App\Enums\PRFEventType;
use App\Enums\PRFResponsibleDesk;
use App\Models\PRFEvent;
use Carbon\Carbon;
use Exception;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class EventSpeakersRelationManager extends RelationManager
{
    protected static string $relationship = 'eventSpeakers';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('prf_event_id')
                    ->label('Event')
                    ->relationship('event', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record?->name ?? 'Unnamed Event')
                    ->createOptionForm([
                        Section::make('Event Details')
                            ->description('Basic event information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Event Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Enter a descriptive name for this event')
                                    ->placeholder('e.g., Annual Conference, Prayer Meeting, Youth Rally'),

                                Select::make('responsible_desk')
                                    ->label('🏢 Responsible Desk')
                                    ->options(PRFResponsibleDesk::getOptions())
                                    ->required()
                                    ->placeholder('Select desk...')
                                    ->helperText('The desk responsible for organizing this event'),

                                Select::make('event_type')
                                    ->label('📅 Event Type')
                                    ->required()
                                    ->options(PRFEventType::getOptions())
                                    ->helperText('Choose the appropriate event category'),

                                Textarea::make('description')
                                    ->label('Event Description')
                                    ->required()
                                    ->rows(4)
                                    ->helperText('Provide a detailed description of the event purpose and activities')
                                    ->placeholder('Describe what this event is about, its purpose, target audience, and what attendees can expect...')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),

                        Section::make('Date & Time')
                            ->description('Event schedule and timing')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('📅 Start Date')
                                    ->native(false)
                                    ->after(today())
                                    ->required()
                                    ->helperText('Select when the event begins')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Auto-set end_date if not already set
                                        if ($state && ! $get('end_date')) {
                                            $set('end_date', $state);
                                        }
                                    }),

                                TimePicker::make('start_time')
                                    ->label('🕐 Start Time')
                                    ->seconds(false)
                                    ->native(false)
                                    ->required()
                                    ->default('08:00')
                                    ->helperText('Event start time')
                                    ->timezone(Auth::user()->timezone),

                                DatePicker::make('end_date')
                                    ->label('📅 End Date')
                                    ->native(false)
                                    ->timezone(Auth::user()->timezone)
                                    ->afterOrEqual('start_date')
                                    ->required()
                                    ->helperText('Select when the event ends'),

                                TimePicker::make('end_time')
                                    ->label('🕐 End Time')
                                    ->seconds(false)
                                    ->native(false)
                                    ->required()
                                    ->default('17:00')
                                    ->helperText('Event end time')
                                    ->timezone(Auth::user()->timezone),
                            ])
                            ->columns(2),

                    ])
                    ->createOptionUsing(function (array $data) {
                        return PRFEvent::create($data)->getKey();
                    }),
                TextInput::make('topic')
                    ->label('🎤 Speaking Topic')
                    ->required()
                    ->maxLength(255)
                    ->helperText('What will this speaker talk about at this event?')
                    ->placeholder('e.g., Faith in Action, The Power of Prayer, Youth Leadership'),
                Textarea::make('description')
                    ->label('Topic Description')
                    ->rows(3)
                    ->maxLength(65535)
                    ->helperText('Detailed description of the speaking topic and key points')
                    ->placeholder('Provide more details about the speaking topic, key points to be covered, target audience, etc.'),
                Textarea::make('comments')
                    ->label('📝 Internal Comments')
                    ->rows(3)
                    ->maxLength(65535)
                    ->helperText('Private notes about this speaking engagement (not visible to public)')
                    ->placeholder('Any special requirements, coordination notes, or internal observations...'),
            ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('topic')
            ->columns([
                TextColumn::make('event.name')
                    ->label('Event')
                    ->description(function ($record) {
                        if (! $record->event?->responsible_desk) {
                            return null;
                        }
                        try {
                            return PRFResponsibleDesk::fromValue($record->event->responsible_desk)->getLabel();
                        } catch (Exception $e) {
                            return 'Unknown Desk';
                        }
                    })
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-m-calendar-days')
                    ->wrap(),
                TextColumn::make('topic')
                    ->label('Speaking Topic')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->wrap()
                    ->icon('heroicon-m-microphone')
                    ->color('primary'),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 60) {
                            return null;
                        }

                        return $state;
                    })
                    ->wrap()
                    ->placeholder('No description provided')
                    ->toggleable(),
                TextColumn::make('event.start_date')
                    ->label('Event Date')
                    ->date('M j, Y')
                    ->description(fn ($record) => $record->event?->start_time ?
                        'at '.Carbon::parse($record->event->start_time)->format('g:i A') : null)
                    ->icon('heroicon-m-clock')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('event.event_type')
                    ->label('Event Type')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return 'Not Set';
                        }
                        try {
                            return PRFEventType::from($state)->name;
                        } catch (Exception $e) {
                            return 'Unknown';
                        }
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        PRFEventType::MEMBER->value => 'success',
                        PRFEventType::LEADERSHIP->value => 'info',
                        default => 'gray'
                    })
                    ->icon('heroicon-m-tag')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('comments')
                    ->label('Has Notes')
                    ->boolean()
                    ->trueIcon('heroicon-o-chat-bubble-left-right')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->state(fn ($record) => ! empty($record->comments))
                    ->tooltip(fn ($record) => ! empty($record->comments) ? 'Has internal notes' : 'No notes'),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Added: '.$record->created_at->format('F j, Y \a\t g:i A')),
            ])
            ->defaultSort('event.start_date', 'desc')
            ->persistSortInSession()
            ->striped()
            ->filters([
                TrashedFilter::make()
                    ->label('Deleted Engagements')
                    ->placeholder('All Engagements'),
                SelectFilter::make('event_type')
                    ->label('Event Type')
                    ->options(PRFEventType::getOptions())
                    ->placeholder('All Event Types')
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] ?? null) {
                            return $query->whereHas('event', fn ($q) => $q->where('event_type', $data['value']));
                        }

                        return $query;
                    }),
                Filter::make('upcoming_events')
                    ->label('Upcoming Events')
                    ->query(fn (Builder $query) => $query->whereHas('event', fn ($q) => $q->where('start_date', '>=', today())))
                    ->default()
                    ->toggle(),
                Filter::make('past_events')
                    ->label('Past Events')
                    ->query(fn (Builder $query) => $query->whereHas('event', fn ($q) => $q->where('end_date', '<', today())))
                    ->toggle(),
                Filter::make('has_notes')
                    ->label('Has Notes')
                    ->query(fn (Builder $query) => $query->whereNotNull('comments'))
                    ->toggle(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Speaking Engagement')
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading('Add Speaking Engagement')
                    ->modalDescription('Schedule this speaker for an event with a specific topic')
                    ->modalWidth('7xl')
                    ->successNotificationTitle('Speaking engagement added successfully')
                    ->color('primary'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->modalHeading(fn ($record) => "Speaking Engagement: {$record->topic}")
                        ->modalDescription(fn ($record) => "Event: {$record->event->name}")
                        ->color('info'),
                    EditAction::make()
                        ->successNotificationTitle('Speaking engagement updated successfully')
                        ->color('warning'),
                    DeleteAction::make()
                        ->successNotificationTitle('Speaking engagement removed successfully')
                        ->color('danger'),
                    ForceDeleteAction::make(),
                    RestoreAction::make()
                        ->successNotificationTitle('Speaking engagement restored successfully'),
                ])->label('Actions')
                    ->color('primary')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->button()
                    ->tooltip('Engagement Actions'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotificationTitle('Speaking engagements removed successfully'),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make()
                        ->successNotificationTitle('Speaking engagements restored successfully'),
                    BulkAction::make('updateComments')
                        ->label('Add Notes')
                        ->icon('heroicon-m-chat-bubble-left-right')
                        ->color('info')
                        ->form([
                            Textarea::make('comments')
                                ->label('Comments')
                                ->rows(3)
                                ->placeholder('Add notes to selected speaking engagements...')
                                ->helperText('These comments will be added to all selected engagements'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['comments' => $data['comments']]);
                            }
                        })
                        ->successNotificationTitle('Comments added to speaking engagements'),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
