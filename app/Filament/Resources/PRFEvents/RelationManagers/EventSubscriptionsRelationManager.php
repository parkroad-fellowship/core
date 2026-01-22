<?php

namespace App\Filament\Resources\PRFEvents\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class EventSubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'eventSubscriptions';

    protected static ?string $recordTitleAttribute = 'member.full_name';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $title = 'Event Subscriptions';

    protected static ?string $modelLabel = 'Subscription';

    protected static ?string $pluralModelLabel = 'Subscriptions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Details')
                    ->description('Manage event subscription information')
                    ->icon('heroicon-o-ticket')
                    ->schema([
                        Select::make('member_id')
                            ->label('Member')
                            ->required()
                            ->relationship('member', 'full_name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Select a member...')
                            ->helperText('Choose the member who is subscribing to this event')
                            ->columnSpanFull(),

                        TextInput::make('number_of_attendees')
                            ->label('Number of Attendees')
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue(6)
                            ->default(1)
                            ->numeric()
                            ->helperText('Maximum 6 attendees per subscription')
                            ->suffixIcon('heroicon-m-users')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('member.full_name')
            ->columns([
                TextColumn::make('member.full_name')
                    ->label('Member')
                    ->description(fn ($record) => $record->member?->email ?? 'No email')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->icon('heroicon-m-user')
                    ->color(Color::Blue),

                TextColumn::make('member.phone_number')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-m-phone')
                    ->color(Color::Gray)
                    ->placeholder('No phone'),

                TextColumn::make('number_of_attendees')
                    ->label('Attendees')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 5 => Color::Red,
                        $state >= 3 => Color::Orange,
                        $state >= 2 => Color::Yellow,
                        default => Color::Green,
                    })
                    ->icon('heroicon-m-users')
                    ->description(fn ($state) => match (true) {
                        $state >= 5 => 'Large group',
                        $state >= 3 => 'Medium group',
                        $state >= 2 => 'Small group',
                        default => 'Individual',
                    }),

                TextColumn::make('prfEvent.title')
                    ->label('Event')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->icon('heroicon-m-calendar-days')
                    ->color(Color::Purple)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('prfEvent.start_date')
                    ->label('Event Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->color(fn ($state) => $state && $state->isPast() ? Color::Gray : Color::Green)
                    ->description(fn ($state) => $state && $state->isPast() ? 'Past event' : 'Upcoming event')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Subscribed On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-m-clock')
                    ->color(Color::Gray)
                    ->description('When the subscription was created'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-m-pencil')
                    ->color(Color::Gray),
            ])
            ->filters([
                TrashedFilter::make()
                    ->native(false),

                SelectFilter::make('number_of_attendees')
                    ->label('Group Size')
                    ->options([
                        '1' => 'Individual (1)',
                        '2' => 'Small Group (2)',
                        '3' => 'Medium Group (3)',
                        '4' => 'Medium Group (4)',
                        '5' => 'Large Group (5)',
                        '6' => 'Large Group (6)',
                    ])
                    ->placeholder('All group sizes')
                    ->multiple()
                    ->native(false),

                Filter::make('recent_subscriptions')
                    ->label('Recent Subscriptions')
                    ->query(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7)))
                    ->indicator('Last 7 days')
                    ->toggle(),

                Filter::make('upcoming_events')
                    ->label('Upcoming Events')
                    ->query(fn (Builder $query) => $query->whereHas('prfEvent', fn ($q) => $q->where('start_date', '>=', now()->toDateString())))
                    ->indicator('Upcoming events only')
                    ->toggle(),

                Filter::make('past_events')
                    ->label('Past Events')
                    ->query(fn (Builder $query) => $query->whereHas('prfEvent', fn ($q) => $q->where('start_date', '<', now()->toDateString())))
                    ->indicator('Past events only')
                    ->toggle(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->headerActions([
                CreateAction::make()
                    ->label('Add Subscription')
                    ->icon('heroicon-m-plus')
                    ->color(Color::Blue)
                    ->modalHeading('Add New Event Subscription')
                    ->modalDescription('Create a new subscription for this event')
                    ->modalSubmitActionLabel('Create Subscription')
                    ->successNotificationTitle('Subscription created successfully!')
                    ->mutateDataUsing(function (array $data): array {
                        $data['prf_event_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->icon('heroicon-m-trash')
                        ->color(Color::Red)
                        ->modalHeading('Delete Selected Subscriptions')
                        ->modalDescription('Are you sure you want to delete the selected subscriptions?')
                        ->modalSubmitActionLabel('Delete Subscriptions')
                        ->successNotificationTitle('Selected subscriptions deleted successfully!'),

                    ForceDeleteBulkAction::make()
                        ->label('Force Delete Selected')
                        ->icon('heroicon-m-x-mark')
                        ->color(Color::Red)
                        ->modalHeading('Permanently Delete Selected Subscriptions')
                        ->modalDescription('This will permanently delete the selected subscriptions. This action cannot be undone.')
                        ->successNotificationTitle('Selected subscriptions permanently deleted!'),

                    RestoreBulkAction::make()
                        ->label('Restore Selected')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->color(Color::Green)
                        ->modalHeading('Restore Selected Subscriptions')
                        ->modalDescription('This will restore the selected deleted subscriptions.')
                        ->successNotificationTitle('Selected subscriptions restored successfully!'),
                ])
                    ->label('Bulk Actions')
                    ->color(Color::Gray),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Information')
                    ->icon('heroicon-o-ticket')
                    ->description('Detailed information about this event subscription')
                    ->schema([
                        TextEntry::make('member.full_name')
                            ->label('Member Name')
                            ->icon('heroicon-m-user')
                            ->color(Color::Blue)
                            ->weight(FontWeight::SemiBold),

                        TextEntry::make('member.email')
                            ->label('Email Address')
                            ->icon('heroicon-m-envelope')
                            ->color(Color::Gray)
                            ->placeholder('No email provided'),

                        TextEntry::make('member.phone_number')
                            ->label('Phone Number')
                            ->icon('heroicon-m-phone')
                            ->color(Color::Gray)
                            ->placeholder('No phone provided'),

                        TextEntry::make('number_of_attendees')
                            ->label('Number of Attendees')
                            ->icon('heroicon-m-users')
                            ->badge()
                            ->color(fn ($state) => match (true) {
                                $state >= 5 => Color::Red,
                                $state >= 3 => Color::Orange,
                                $state >= 2 => Color::Yellow,
                                default => Color::Green,
                            })
                            ->formatStateUsing(fn ($state) => $state.' '.str($state == 1 ? 'attendee' : 'attendees')->title()),
                    ])
                    ->columns(2),

                Section::make('Event Details')
                    ->icon('heroicon-o-calendar-days')
                    ->description('Information about the associated event')
                    ->schema([
                        TextEntry::make('prfEvent.title')
                            ->label('Event Title')
                            ->icon('heroicon-m-calendar-days')
                            ->color(Color::Purple)
                            ->weight(FontWeight::SemiBold),

                        TextEntry::make('prfEvent.description')
                            ->label('Event Description')
                            ->icon('heroicon-m-document-text')
                            ->color(Color::Gray)
                            ->placeholder('No description provided')
                            ->limit(100),

                        TextEntry::make('prfEvent.start_date')
                            ->label('Event Date')
                            ->icon('heroicon-m-calendar')
                            ->color(fn ($state) => $state && $state->isPast() ? Color::Gray : Color::Green)
                            ->formatStateUsing(fn ($state) => $state ? $state->format('F j, Y') : 'Not set'),

                        TextEntry::make('prfEvent.event_subscriptions_count')
                            ->label('Total Subscriptions')
                            ->icon('heroicon-m-users')
                            ->badge()
                            ->color(Color::Blue)
                            ->formatStateUsing(fn ($state) => ($state ?? 0).' '.str($state == 1 ? 'subscription' : 'subscriptions')->title()),
                    ])
                    ->columns(2),

                Section::make('Subscription Timeline')
                    ->icon('heroicon-o-clock')
                    ->description('Timeline of subscription activities')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Subscription Created')
                            ->icon('heroicon-m-plus-circle')
                            ->color(Color::Green)
                            ->dateTime('F j, Y \a\t g:i A T')
                            ->timezone(Auth::user()->timezone),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->icon('heroicon-m-pencil')
                            ->color(Color::Orange)
                            ->dateTime('F j, Y \a\t g:i A T')
                            ->timezone(Auth::user()->timezone),

                        TextEntry::make('deleted_at')
                            ->label('Deleted At')
                            ->icon('heroicon-m-trash')
                            ->color(Color::Red)
                            ->dateTime('F j, Y \a\t g:i A T')
                            ->timezone(Auth::user()->timezone)
                            ->placeholder('Not deleted')
                            ->visible(fn ($record) => $record->trashed()),
                    ])
                    ->columns(2),
            ]);
    }
}
