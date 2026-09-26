<?php

namespace App\Filament\Resources\MissionTypes\Pages;

use App\Filament\Resources\MissionTypes\MissionTypeResource;
use App\Models\MissionType;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMissionType extends EditRecord
{
    protected static string $resource = MissionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(MissionType::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(MissionType::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(MissionType::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(MissionType::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MissionType::permission('edit'));
    }
}
