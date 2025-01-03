<?php

namespace App\Filament\Resources\SpiritualYearResource\Pages;

use App\Filament\Resources\SpiritualYearResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSpiritualYear extends CreateRecord
{
    protected static string $resource = SpiritualYearResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create spiritual year');
    }
}
