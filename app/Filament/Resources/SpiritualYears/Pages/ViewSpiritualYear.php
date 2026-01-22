<?php

namespace App\Filament\Resources\SpiritualYears\Pages;

use App\Filament\Resources\SpiritualYears\SpiritualYearResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSpiritualYear extends ViewRecord
{
    protected static string $resource = SpiritualYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view spiritual year');
    }
}
