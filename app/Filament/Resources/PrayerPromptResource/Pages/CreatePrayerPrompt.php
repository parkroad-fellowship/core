<?php

namespace App\Filament\Resources\PrayerPromptResource\Pages;

use App\Filament\Resources\PrayerPromptResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePrayerPrompt extends CreateRecord
{
    protected static string $resource = PrayerPromptResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create prayer prompt');
    }
}
