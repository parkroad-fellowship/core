<?php

namespace App\Filament\Resources\ClassGroups\Pages;

use App\Filament\Resources\ClassGroups\ClassGroupResource;
use App\Models\ClassGroup;
use Filament\Resources\Pages\CreateRecord;

class CreateClassGroup extends CreateRecord
{
    protected static string $resource = ClassGroupResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(ClassGroup::permission('create'));
    }
}
