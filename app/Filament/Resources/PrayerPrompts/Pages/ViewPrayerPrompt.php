<?php

namespace App\Filament\Resources\PrayerPrompts\Pages;

use App\Filament\Resources\PrayerPrompts\PrayerPromptResource;
use App\Models\PrayerPrompt;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPrayerPrompt extends ViewRecord
{
    protected static string $resource = PrayerPromptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn() => userCan(PrayerPrompt::permission('edit'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(PrayerPrompt::permission('view'));
    }
}
