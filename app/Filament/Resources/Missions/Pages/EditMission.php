<?php

namespace App\Filament\Resources\Missions\Pages;

use App\Filament\Actions\CompleteMissionAction;
use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Missions\MissionResource;
use App\Models\Mission;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMission extends EditRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = MissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CompleteMissionAction::make(),
            MissionResource::getNotificationActions(),
            MissionResource::getReportActions(),
            MissionResource::getAIToolsActions(),
            ViewAction::make()->visible(fn() => userCan(Mission::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Mission::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Mission::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Mission::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Mission::permission('edit'));
    }
}
