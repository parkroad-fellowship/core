<?php

namespace App\Filament\Resources\PrayerPromptResource\Pages;

use App\Filament\Resources\PrayerPromptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrayerPrompts extends ListRecords
{
    protected static string $resource = PrayerPromptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
