<?php

namespace App\Filament\Resources\ClassGroups\Pages;

use App\Filament\Resources\ClassGroups\ClassGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClassGroup extends CreateRecord
{
    protected static string $resource = ClassGroupResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create class group');
    }
}
