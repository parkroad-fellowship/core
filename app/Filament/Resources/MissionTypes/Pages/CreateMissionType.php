<?php

namespace App\Filament\Resources\MissionTypes\Pages;

use App\Filament\Resources\MissionTypes\MissionTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMissionType extends CreateRecord
{
    protected static string $resource = MissionTypeResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create mission type');
    }
}
