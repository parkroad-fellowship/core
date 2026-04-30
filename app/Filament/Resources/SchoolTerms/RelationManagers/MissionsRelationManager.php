<?php

namespace App\Filament\Resources\SchoolTerms\RelationManagers;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFMissionStatus;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'missions';

    protected static ?string $recordTitleAttribute = 'school.name';

    protected static ?string $navigationIcon = 'heroicon-o-globe-americas';

    protected static ?string $title = 'Missions';

    protected static ?string $modelLabel = 'Mission';

    protected static ?string $pluralModelLabel = 'Missions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mission Details')
                    ->description('Configure mission information and assignment')
                    ->icon('heroicon-o-globe-americas')
                    ->schema([
                        Select::make('school_id')
                            ->label('School')
                            ->required()
                            ->relationship('school', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Select a school...')
                            ->helperText('Choose the school for this mission')
                            ->prefixIcon('heroicon-m-academic-cap')
                            ->columnSpan(2),

                        Select::make('mission_type_id')
                            ->label('Mission Type')
                            ->required()
                            ->relationship(
                                name: 'missionType',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Select mission type...')
                            ->helperText('Choose the type of mission to be conducted')
                            ->prefixIcon('heroicon-m-tag')
                            ->columnSpan(2),

                        Select::make('status')
                            ->label('Mission Status')
                            ->required()
                            ->options(PRFMissionStatus::getOptions())
                            ->default(PRFMissionStatus::PENDING->value)
                            ->native(false)
                            ->placeholder('Select status...')
                            ->helperText('Current status of the mission')
                            ->prefixIcon('heroicon-m-flag')
                            ->columnSpan(2),
                    ])
                    ->columns(4)
                    ->collapsible(),

                Section::make('Schedule')
                    ->description('Set mission dates and timing')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->native(false)
                            ->required()
                            ->placeholder('Select start date...')
                            ->helperText('When the mission begins')
                            ->prefixIcon('heroicon-m-calendar')
                            ->displayFormat('M j, Y')
                            ->closeOnDateSelection()
                            ->columnSpan(1),

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->native(false)
                            ->placeholder('Select end date...')
                            ->helperText('When the mission ends (optional)')
                            ->prefixIcon('heroicon-m-calendar')
                            ->displayFormat('M j, Y')
                            ->closeOnDateSelection()
                            ->after('start_date')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Preparation Notes')
                    ->description('Additional notes and preparation details')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Textarea::make('mission_prep_notes')
                            ->label('Preparation Notes')
                            ->placeholder('Enter any preparation notes, requirements, or special instructions...')
                            ->helperText('Document any special preparation requirements or notes for this mission')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('school.name')
            ->columns([
                TextColumn::make('school.name')
                    ->label('School')
                    ->description(fn ($record) => $record->school?->address ?? 'No address')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->icon('heroicon-m-academic-cap')
                    ->color(Color::Blue),

                TextColumn::make('missionType.name')
                    ->label('Mission Type')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight(FontWeight::Medium)
                    ->icon('heroicon-m-tag')
                    ->color(Color::Purple),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PRFMissionStatus::fromValue($state)->getLabel())
                    ->color(fn ($state) => PRFMissionStatus::fromValue($state)->getColor())
                    ->sortable()
                    ->icon(fn ($state) => match (PRFMissionStatus::fromValue($state)) {
                        PRFMissionStatus::PENDING => 'heroicon-m-clock',
                        PRFMissionStatus::APPROVED => 'heroicon-m-check-circle',
                        PRFMissionStatus::REJECTED => 'heroicon-m-x-circle',
                        PRFMissionStatus::FULLY_SUBSCRIBED => 'heroicon-m-user-group',
                        PRFMissionStatus::CANCELLED => 'heroicon-m-no-symbol',
                        PRFMissionStatus::SERVICED => 'heroicon-m-check-badge',
                        PRFMissionStatus::POSTPONED => 'heroicon-m-pause-circle',
                    }),

                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->color(fn ($state) => $state && $state->isPast() ? Color::Gray : Color::Green)
                    ->description(fn ($state) => $state ? ($state->isPast() ? 'Started' : 'Upcoming') : 'No date set'),

                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-m-calendar')
                    ->color(Color::Orange)
                    ->placeholder('No end date'),

                TextColumn::make('mission_duration')
                    ->label('Duration')
                    ->getStateUsing(function ($record) {
                        if (! $record->start_date || ! $record->end_date) {
                            return 'TBD';
                        }
                        $days = $record->start_date->diffInDays($record->end_date) + 1;

                        return $days.' '.str($days == 1 ? 'day' : 'days')->title();
                    })
                    ->badge()
                    ->color(Color::Cyan)
                    ->icon('heroicon-m-clock')
                    ->toggleable(),

                TextColumn::make('mission_prep_notes')
                    ->label('Prep Notes')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->mission_prep_notes)
                    ->placeholder('No notes')
                    ->icon('heroicon-m-document-text')
                    ->color(Color::Gray)
                    ->toggleable(isToggledHiddenByDefault: true),

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
                SelectFilter::make('status')
                    ->label('Mission Status')
                    ->options(PRFMissionStatus::getOptions())
                    ->placeholder('All statuses')
                    ->multiple()
                    ->native(false),

                SelectFilter::make('mission_type_id')
                    ->label('Mission Type')
                    ->relationship('missionType', 'name')
                    ->placeholder('All types')
                    ->multiple()
                    ->preload()
                    ->native(false),

                SelectFilter::make('school_id')
                    ->label('School')
                    ->relationship('school', 'name')
                    ->placeholder('All schools')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->native(false),

                Filter::make('upcoming_missions')
                    ->label('Upcoming Missions')
                    ->query(fn (Builder $query) => $query->where('start_date', '>=', now()->toDateString()))
                    ->indicator('Upcoming missions only')
                    ->toggle(),

                Filter::make('active_missions')
                    ->label('Active Missions')
                    ->query(fn (Builder $query) => $query->whereIn('status', [
                        PRFMissionStatus::APPROVED->value,
                        PRFMissionStatus::FULLY_SUBSCRIBED->value,
                    ]))
                    ->indicator('Active missions only')
                    ->toggle(),

                Filter::make('completed_missions')
                    ->label('Completed Missions')
                    ->query(fn (Builder $query) => $query->where('status', PRFMissionStatus::SERVICED->value))
                    ->indicator('Completed missions only')
                    ->toggle(),

                Filter::make('current_month')
                    ->label('This Month')
                    ->query(fn (Builder $query) => $query->whereMonth('start_date', now()->month)
                        ->whereYear('start_date', now()->year))
                    ->indicator('Current month')
                    ->toggle(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)

            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve_missions')
                        ->label('Approve Selected')
                        ->icon('heroicon-m-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === PRFMissionStatus::PENDING->value) {
                                    $record->update(['status' => PRFMissionStatus::APPROVED->value]);
                                }
                            });
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Approve Selected Missions')
                        ->modalDescription('Are you sure you want to approve the selected missions?')
                        ->modalSubmitActionLabel('Approve Missions')
                        ->successNotificationTitle('Selected missions approved successfully!'),

                    BulkAction::make('reject_missions')
                        ->label('Reject Selected')
                        ->icon('heroicon-m-x-circle')
                        ->color(Color::Red)
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === PRFMissionStatus::PENDING->value) {
                                    $record->update(['status' => PRFMissionStatus::REJECTED->value]);
                                }
                            });
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Reject Selected Missions')
                        ->modalDescription('Are you sure you want to reject the selected missions?')
                        ->modalSubmitActionLabel('Reject Missions')
                        ->successNotificationTitle('Selected missions rejected!'),

                    DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->icon('heroicon-m-trash')
                        ->color(Color::Red)
                        ->modalHeading('Delete Selected Missions')
                        ->modalDescription('Are you sure you want to delete the selected missions?')
                        ->modalSubmitActionLabel('Delete Missions')
                        ->successNotificationTitle('Selected missions deleted successfully!'),
                ])
                    ->label('Bulk Actions')
                    ->color(Color::Gray),
            ])
            ->defaultSort('start_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mission Overview')
                    ->icon('heroicon-o-globe-americas')
                    ->description('Comprehensive mission information')
                    ->schema([
                        TextEntry::make('school.name')
                            ->label('School')
                            ->color(Color::Blue)
                            ->weight(FontWeight::SemiBold),

                        TextEntry::make('school.address')
                            ->label('School Address')
                            ->icon('heroicon-m-map-pin')
                            ->color(Color::Gray)
                            ->placeholder('No address provided'),

                        TextEntry::make('missionType.name')
                            ->label('Mission Type')
                            ->icon('heroicon-m-tag')
                            ->color(Color::Purple)
                            ->weight(FontWeight::Medium),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => PRFMissionStatus::fromValue($state)->getLabel())
                            ->color(fn ($state) => PRFMissionStatus::fromValue($state)->getColor())
                            ->icon(fn ($state) => match (PRFMissionStatus::fromValue($state)) {
                                PRFMissionStatus::PENDING => 'heroicon-m-clock',
                                PRFMissionStatus::APPROVED => 'heroicon-m-check-circle',
                                PRFMissionStatus::REJECTED => 'heroicon-m-x-circle',
                                PRFMissionStatus::FULLY_SUBSCRIBED => 'heroicon-m-user-group',
                                PRFMissionStatus::CANCELLED => 'heroicon-m-no-symbol',
                                PRFMissionStatus::SERVICED => 'heroicon-m-check-badge',
                                PRFMissionStatus::POSTPONED => 'heroicon-m-pause-circle',
                            }),
                    ])
                    ->columns(2),

                Section::make('Schedule & Duration')
                    ->icon('heroicon-o-calendar-days')
                    ->description('Mission timing and duration details')
                    ->schema([
                        TextEntry::make('start_date')
                            ->label('Start Date')
                            ->icon('heroicon-m-calendar')
                            ->color(fn ($state) => $state && $state->isPast() ? Color::Gray : Color::Green)
                            ->formatStateUsing(fn ($state) => $state ? $state->format('F j, Y (l)') : 'Not set'),

                        TextEntry::make('end_date')
                            ->label('End Date')
                            ->icon('heroicon-m-calendar')
                            ->color(Color::Orange)
                            ->formatStateUsing(fn ($state) => $state ? $state->format('F j, Y (l)') : 'Not set')
                            ->placeholder('No end date set'),

                        TextEntry::make('mission_duration')
                            ->label('Duration')
                            ->getStateUsing(function ($record) {
                                if (! $record->start_date || ! $record->end_date) {
                                    return 'To be determined';
                                }
                                $days = $record->start_date->diffInDays($record->end_date) + 1;

                                return $days.' '.str($days == 1 ? 'day' : 'days')->title();
                            })
                            ->icon('heroicon-m-clock')
                            ->badge()
                            ->color(Color::Cyan),

                        TextEntry::make('days_until_start')
                            ->label('Days Until Start')
                            ->getStateUsing(function ($record) {
                                if (! $record->start_date) {
                                    return 'Date not set';
                                }
                                $days = now()->diffInDays($record->start_date, false);
                                if ($days < 0) {
                                    return 'Started '.abs($days).' days ago';
                                } elseif ($days == 0) {
                                    return 'Starting today';
                                } else {
                                    return $days.' days remaining';
                                }
                            })
                            ->icon('heroicon-m-clock')
                            ->badge()
                            ->color(function ($record) {
                                if (! $record->start_date) {
                                    return Color::Gray;
                                }
                                $days = now()->diffInDays($record->start_date, false);

                                return match (true) {
                                    $days < 0 => Color::Gray,
                                    $days <= 7 => Color::Red,
                                    $days <= 30 => Color::Orange,
                                    default => Color::Green,
                                };
                            }),
                    ])
                    ->columns(2),

                Section::make('Preparation Details')
                    ->icon('heroicon-o-document-text')
                    ->description('Mission preparation notes and requirements')
                    ->schema([
                        TextEntry::make('mission_prep_notes')
                            ->label('Preparation Notes')
                            ->icon('heroicon-m-document-text')
                            ->color(Color::Gray)
                            ->placeholder('No preparation notes provided')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => ! empty($record->mission_prep_notes)),

                Section::make('Mission Timeline')
                    ->icon('heroicon-o-clock')
                    ->description('Mission creation and modification history')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Mission Created')
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

                        TextEntry::make('schoolTerm.name')
                            ->label('School Term')
                            ->icon('heroicon-m-academic-cap')
                            ->color(Color::Blue)
                            ->placeholder('No term specified'),

                        TextEntry::make('schoolTerm.start_date')
                            ->label('Term Start')
                            ->icon('heroicon-m-calendar')
                            ->color(Color::Gray)
                            ->formatStateUsing(fn ($state) => $state ? $state->format('M j, Y') : 'Not set'),
                    ])
                    ->columns(2),
            ]);
    }

    protected function canCreate(): bool
    {
        return userCan('create mission');
    }

    protected function canEdit($record): bool
    {
        return userCan('edit mission');
    }

    protected function canDelete($record): bool
    {
        return userCan('delete mission');
    }

    protected function canView($record): bool
    {
        return userCan('view mission');
    }
}
