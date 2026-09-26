<?php

namespace App\Filament\Resources\SchoolTerms\RelationManagers;

use App\Enums\PRFMissionStatus;
use App\Filament\Resources\MissionPlanner\MissionPlannerResource;
use App\Models\Mission;
use App\States\Mission\MissionState;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The term's missions, for reference. Missions are planned and managed from the Missions screen.
 */
class MissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'missions';

    protected static ?string $title = 'Missions this term';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->label('School')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn(Mission $record): ?string => $record->missionType?->name),
                TextColumn::make('start_date')->label('When')->date('D j M Y')->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(MissionState $state): string => $state->getLabel())
                    ->color(fn(MissionState $state): string => $state->getColor()),
                TextColumn::make('capacity')->label('Missioners needed')->alignCenter(),
            ])
            ->recordUrl(fn(Mission $record): string => MissionPlannerResource::getUrl('view', ['record' => $record]))
            ->defaultSort('start_date', 'desc')
            ->emptyStateHeading('No missions this term yet');
    }
}
