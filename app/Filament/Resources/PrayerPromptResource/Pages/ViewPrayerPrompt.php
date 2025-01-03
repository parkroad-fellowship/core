<?php

namespace App\Filament\Resources\PrayerPromptResource\Pages;

use App\Filament\Resources\PrayerPromptResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPrayerPrompt extends ViewRecord
{
    protected static string $resource = PrayerPromptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => userCan('edit prayer prompt')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view module');
    }
}
