<?php

namespace App\Filament\Resources\Souls\Pages;

use App\Filament\Resources\Souls\SoulResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSoul extends CreateRecord
{
    protected static string $resource = SoulResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create soul');
    }
}
