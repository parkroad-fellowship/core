<?php

namespace App\Filament\Resources\Professions\Pages;

use App\Filament\Resources\Professions\ProfessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProfession extends CreateRecord
{
    protected static string $resource = ProfessionResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create profession');
    }
}
