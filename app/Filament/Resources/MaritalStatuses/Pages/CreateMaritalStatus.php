<?php

namespace App\Filament\Resources\MaritalStatuses\Pages;

use App\Filament\Resources\MaritalStatuses\MaritalStatusResource;
use App\Models\MaritalStatus;
use Filament\Resources\Pages\CreateRecord;

class CreateMaritalStatus extends CreateRecord
{
    protected static string $resource = MaritalStatusResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MaritalStatus::permission('create'));
    }
}
