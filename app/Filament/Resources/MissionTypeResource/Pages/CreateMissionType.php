<?php

namespace App\Filament\Resources\MissionTypeResource\Pages;

use App\Filament\Resources\MissionTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMissionType extends CreateRecord
{
    protected static string $resource = MissionTypeResource::class;
    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
