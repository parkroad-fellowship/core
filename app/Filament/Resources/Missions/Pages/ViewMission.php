<?php

namespace App\Filament\Resources\Missions\Pages;

use App\Filament\Actions\CompleteMissionAction;
use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\MissionPlanner\MissionPlannerResource;
use App\Filament\Resources\Missions\MissionResource;
use App\Models\Mission;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMission extends ViewRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = MissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('guidedView')
                ->label('Guided view')
                ->icon('heroicon-m-map')
                ->color('gray')
                ->link()
                ->url(fn(Mission $record): string => MissionPlannerResource::getUrl('view', ['record' => $record])),
            CompleteMissionAction::make(),
            MissionResource::getNotificationActions(),
            MissionResource::getReportActions(),
            MissionResource::getAIToolsActions(),
            EditAction::make()->visible(fn() => userCan(Mission::permission('edit'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Mission::permission('view'));
    }
}
