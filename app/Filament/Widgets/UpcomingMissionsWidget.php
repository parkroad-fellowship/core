<?php

namespace App\Filament\Widgets;

use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Filament\Resources\MissionPlanner\MissionPlannerResource;
use App\Models\Mission;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Approved missions in the next two weeks, with how full each team is: the secretary's to-do list.
 */
class UpcomingMissionsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected static ?string $heading = 'Coming up in the next two weeks';

    public static function canView(): bool
    {
        return userCan(Mission::permission('viewAny'));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Mission::query()
                    ->whereIn('status', [PRFMissionStatus::APPROVED, PRFMissionStatus::FULLY_SUBSCRIBED])
                    ->whereBetween('start_date', [today(), today()->addDays(14)])
                    ->with(['school', 'missionType']),
            )
            ->columns([
                TextColumn::make('school.name')
                    ->label('School')
                    ->weight('semibold')
                    ->description(fn(Mission $record): ?string => $record->missionType?->name),
                TextColumn::make('start_date')
                    ->label('When')
                    ->date('D j M')
                    ->description(fn(Mission $record): string => $record->start_date->diffForHumans()),
                TextColumn::make('approved_subscriptions_count')
                    ->label('Team')
                    ->counts([
                        'missionSubscriptions as approved_subscriptions_count' => fn(Builder $query) => $query->where(
                            'status',
                            PRFMissionSubscriptionStatus::APPROVED,
                        ),
                    ])
                    ->formatStateUsing(fn(int $state, Mission $record): string => "{$state} / {$record->capacity}")
                    ->badge()
                    ->color(fn(int $state, Mission $record): string => $state >= $record->capacity
                        ? 'success'
                        : 'warning'),
            ])
            ->recordUrl(fn(Mission $record): string => MissionPlannerResource::getUrl('view', ['record' => $record]))
            ->defaultSort('start_date')
            ->paginated(false)
            ->emptyStateHeading('Nothing in the next two weeks')
            ->emptyStateDescription('Approved missions show up here as their date gets close.');
    }
}
