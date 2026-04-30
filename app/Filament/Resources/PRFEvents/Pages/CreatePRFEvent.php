<?php

namespace App\Filament\Resources\PRFEvents\Pages;

use App\Filament\Resources\PRFEvents\PRFEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePRFEvent extends CreateRecord
{
    protected static string $resource = PRFEventResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create event');
    }
}
