<?php

namespace App\Filament\Resources\Churches\Pages;

use App\Filament\Resources\Churches\ChurchResource;
use App\Models\Church;
use Filament\Resources\Pages\CreateRecord;

class CreateChurch extends CreateRecord
{
    protected static string $resource = ChurchResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Church::permission('create'));
    }
}
