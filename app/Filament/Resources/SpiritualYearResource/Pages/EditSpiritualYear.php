<?php

namespace App\Filament\Resources\SpiritualYearResource\Pages;

use App\Filament\Resources\SpiritualYearResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSpiritualYear extends EditRecord
{
    protected static string $resource = SpiritualYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
