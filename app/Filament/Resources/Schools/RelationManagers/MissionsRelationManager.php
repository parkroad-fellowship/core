<?php

namespace App\Filament\Resources\Schools\RelationManagers;

use App\Enums\PRFMissionStatus;
use App\Filament\Forms\Schemas\SchoolSchema;
use App\Filament\Resources\MissionPlanner\MissionPlannerResource;
use App\Models\Mission;
use App\Models\School;
use App\States\Mission\MissionState;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The school's missions, newest first. Read-only: missions are planned and changed on the
 * mission's own page.
 */
class MissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'missions';

    protected static ?string $title = 'Missions';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-calendar-days';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return userCan(Mission::permission('viewAny'));
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('theme')
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->with('missionType'))
            ->columns([
                TextColumn::make('start_date')->label('Date')->date('D j M Y')->sortable(),
                TextColumn::make('missionType.name')->label('Type')->placeholder('Not set'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(?MissionState $state): string => $state?->getLabel() ?? 'Unknown')
                    ->color(fn(?MissionState $state): string => $state?->getColor() ?? 'gray'),
                TextColumn::make('team')
                    ->label('Team')
                    ->state(fn(Mission $record): string => sprintf(
                        '%d/%s',
                        is_numeric($record->getAttribute('approved_subscriptions_count'))
                            ? (int) $record->getAttribute('approved_subscriptions_count')
                            : 0,
                        $record->capacity ?? '–',
                    ))
                    ->tooltip('Approved members / places'),
            ])
            ->defaultSort('start_date', 'desc')
            ->recordUrl(fn(Mission $record): ?string => (
                userCan(Mission::permission('view'))
                    ? MissionPlannerResource::getUrl('view', ['record' => $record])
                    : null
            ))
            ->headerActions([
                Action::make('planMission')
                    ->label('Plan a mission here')
                    ->icon('heroicon-m-calendar-days')
                    ->url(fn(): string => SchoolSchema::planMissionUrl($this->school()))
                    ->visible(fn(): bool => !$this->school()->trashed() && userCan(Mission::permission('create'))),
            ])
            ->emptyStateHeading('No missions here yet')
            ->emptyStateDescription('Use “Plan a mission here” to add the first one.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    private function school(): School
    {
        $school = $this->getOwnerRecord();
        assert($school instanceof School);

        return $school;
    }
}
