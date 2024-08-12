<?php

namespace App\Filament\Resources\SpiritualYearResource\Pages;

use App\Filament\Resources\SpiritualYearResource;
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
}
