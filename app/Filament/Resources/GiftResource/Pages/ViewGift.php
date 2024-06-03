<?php

namespace App\Filament\Resources\GiftResource\Pages;

use App\Filament\Resources\GiftResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGift extends ViewRecord
{
    protected static string $resource = GiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
