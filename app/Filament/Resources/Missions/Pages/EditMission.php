<?php

namespace App\Filament\Resources\Missions\Pages;

use App\Filament\Actions\CompleteMissionAction;
use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Missions\MissionResource;
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
            ViewAction::make()->visible(fn () => userCan('view mission')),
            DeleteAction::make()->visible(fn () => userCan('delete mission')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete mission')),
            RestoreAction::make()->visible(fn () => userCan('restore mission')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit mission');
    }
}
