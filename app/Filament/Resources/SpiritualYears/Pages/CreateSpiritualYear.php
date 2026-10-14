<?php

namespace App\Filament\Resources\SpiritualYears\Pages;

use App\Filament\Resources\SpiritualYears\SpiritualYearResource;
use App\Models\SpiritualYear;
use Filament\Resources\Pages\CreateRecord;

class CreateSpiritualYear extends CreateRecord
{
    protected static string $resource = SpiritualYearResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(SpiritualYear::permission('create'));
    }
}
