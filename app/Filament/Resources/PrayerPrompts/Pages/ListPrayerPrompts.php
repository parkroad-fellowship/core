<?php

namespace App\Filament\Resources\PrayerPrompts\Pages;

use App\Filament\Resources\PrayerPrompts\PrayerPromptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrayerPrompts extends ListRecords
{
    protected static string $resource = PrayerPromptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create prayer prompt')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny prayer prompt');
    }
}
