<?php

namespace App\Filament\Resources\Members\RelationManagers;

use App\Enums\PRFMissionRole;
use App\Enums\PRFMissionSubscriptionStatus;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MissionSubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'missionSubscriptions';

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $label = 'Mission Subscription';

    protected static ?string $pluralLabel = 'Mission Subscriptions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('🎯 Mission Subscription Details')
                    ->description('Mission participation and role assignment')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                Select::make('mission_id')
                                    ->label('🎯 Mission/School')
                                    ->helperText('Select the mission or school to subscribe to')
                                    ->required()
                                    ->relationship('mission.school', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false),

                                Select::make('mission_role')
                                    ->label('👤 Mission Role')
                                    ->helperText('Role within the mission team')
                                    ->required()
                                    ->options(PRFMissionRole::getFilterOptions())
                                    ->default(PRFMissionRole::MEMBER->value)
                                    ->native(false),
                            ]),

                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                Select::make('status')
                                    ->label('📊 Subscription Status')
                                    ->helperText('Current status of the mission subscription')
                                    ->required()
                                    ->options(PRFMissionSubscriptionStatus::getFilterOptions())
                                    ->default(PRFMissionSubscriptionStatus::PENDING->value)
                                    ->native(false)
                                    ->hiddenOn(['create']),

                                DateTimePicker::make('created_at')
                                    ->label('📅 Subscription Date')
                                    ->helperText('Date when member subscribed to the mission')
                                    ->seconds(false)
                                    ->timezone(Auth::user()->timezone)
                                    ->native(false)
                                    ->default(now()),
                            ]),

                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('mission.school.name')
            ->columns([
                TextColumn::make('mission.school.name')
                    ->label('🎯 Mission/School')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->wrap()
                    ->tooltip('Mission school name'),

                TextColumn::make('status')
                    ->badge()
                    ->label('📊 Status')
                    ->formatStateUsing(fn ($record) => PRFMissionSubscriptionStatus::fromValue($record->status)->getLabel())
                    ->color(fn ($record) => PRFMissionSubscriptionStatus::fromValue($record->status)->getColor())
                    ->icon(fn ($record) => PRFMissionSubscriptionStatus::fromValue($record->status)->getIcon())
                    ->size('lg')
                    ->sortable()
                    ->tooltip('Subscription status'),

                TextColumn::make('mission_role')
                    ->badge()
                    ->label('👤 Role')
                    ->formatStateUsing(fn ($record) => PRFMissionRole::fromValue($record->mission_role)->getLabel())
                    ->color(fn ($record) => PRFMissionRole::fromValue($record->mission_role)->getColor())
                    ->icon(fn ($record) => PRFMissionRole::fromValue($record->mission_role)->getIcon())
                    ->sortable()
                    ->tooltip('Mission role'),

                TextColumn::make('mission.start_date')
                    ->label('📅 Start Date')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip('Mission start date'),

                TextColumn::make('mission.end_date')
                    ->label('📅 End Date')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip('Mission end date'),

                TextColumn::make('created_at')
                    ->label('📅 Added')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date subscription was recorded'),

                TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Last modification date'),
            ])
            ->filters([
                PRFMissionSubscriptionStatus::getTableFilter(),

                PRFMissionRole::getTableFilter()
                    ->multiple(),

                SelectFilter::make('mission')
                    ->label('Mission/School')
                    ->relationship('mission.school', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('mission_dates')
                    ->label('Mission Period')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                DatePicker::make('from_date')
                                    ->label('From Date')
                                    ->native(false),
                                DatePicker::make('to_date')
                                    ->label('To Date')
                                    ->native(false),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereHas(
                                    'mission',
                                    fn (Builder $query) => $query->whereDate('start_date', '>=', $date)
                                ),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereHas(
                                    'mission',
                                    fn (Builder $query) => $query->whereDate('end_date', '<=', $date)
                                ),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from_date'] ?? null) {
                            $indicators[] = 'From: '.Carbon::parse($data['from_date'])->toFormattedDateString();
                        }
                        if ($data['to_date'] ?? null) {
                            $indicators[] = 'To: '.Carbon::parse($data['to_date'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                TernaryFilter::make('has_motivation')
                    ->label('Has Motivation')
                    ->placeholder('All subscriptions')
                    ->trueLabel('With motivation')
                    ->falseLabel('Without motivation')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('motivation'),
                        false: fn (Builder $query) => $query->whereNull('motivation'),
                    ),

                TernaryFilter::make('has_special_skills')
                    ->label('Has Special Skills')
                    ->placeholder('All subscriptions')
                    ->trueLabel('With skills')
                    ->falseLabel('Without skills')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('special_skills'),
                        false: fn (Builder $query) => $query->whereNull('special_skills'),
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green)
                    ->visible(fn () => $this->canCreate())
                    ->after(function ($record) {
                        $missionName = $record->mission->school->name ?? 'Unknown Mission';
                        $roleName = PRFMissionRole::fromValue($record->mission_role)->name;

                        Notification::make()
                            ->title('Mission subscription created')
                            ->body("Subscribed to '{$missionName}' as {$roleName}.")
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color(Color::Green)
                    ->action(function ($record) {
                        $record->update(['status' => PRFMissionSubscriptionStatus::APPROVED]);
                        Notification::make()
                            ->title('Subscription approved')
                            ->body('Mission subscription has been approved.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->status === PRFMissionSubscriptionStatus::PENDING->value)
                    ->tooltip('Approve this subscription'),

                Action::make('promote')
                    ->label('Promote')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color(Color::Blue)
                    ->schema([
                        Select::make('new_role')
                            ->label('New Role')
                            ->options(PRFMissionRole::getOptions())
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update(['mission_role' => $data['new_role']]);
                        $newRoleName = PRFMissionRole::fromValue($data['new_role'])->name;

                        Notification::make()
                            ->title('Role updated')
                            ->body("Mission role updated to {$newRoleName}.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->mission_role === PRFMissionRole::MEMBER->value)
                    ->tooltip('Promote to leadership role'),

                ViewAction::make()
                    ->color(Color::Gray),

                EditAction::make()
                    ->color(Color::Orange)
                    ->visible(fn () => $this->canCreate())
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Subscription updated')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->color(Color::Red)
                    ->visible(fn () => $this->canCreate()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve_subscriptions')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $count = $records->where('status', PRFMissionSubscriptionStatus::PENDING)->count();
                            $records->each(function ($record) {
                                if ($record->status === PRFMissionSubscriptionStatus::PENDING->value) {
                                    $record->update(['status' => PRFMissionSubscriptionStatus::APPROVED]);
                                }
                            });

                            Notification::make()
                                ->title('Subscriptions approved')
                                ->body("{$count} mission subscriptions have been approved.")
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->color(Color::Red)
                        ->visible(fn () => $this->canCreate()),
                ])->visible(fn () => $this->canCreate()),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }

    protected function canCreate(): bool
    {
        return userCan('create mission subscription');
    }
}
