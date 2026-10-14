<?php

namespace App\Filament\Resources\MissionTypes\Pages;

use App\Filament\Resources\MissionTypes\MissionTypeResource;
use App\Models\MissionType;
use Filament\Resources\Pages\CreateRecord;

class CreateMissionType extends CreateRecord
{
    protected static string $resource = MissionTypeResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MissionType::permission('create'));
    }
}
