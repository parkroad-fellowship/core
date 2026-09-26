<?php

namespace App\Filament\Resources\Missions\Pages;

use App\Filament\Resources\Missions\MissionResource;
use App\Models\Mission;
use Filament\Resources\Pages\CreateRecord;

class CreateMission extends CreateRecord
{
    protected static string $resource = MissionResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Mission::permission('create'));
    }
}
