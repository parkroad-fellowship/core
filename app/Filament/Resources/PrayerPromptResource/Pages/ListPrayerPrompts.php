<?php

namespace App\Filament\Resources\PrayerPromptResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PrayerPromptResource;
use Filament\Actions;
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
