<?php

namespace App\Filament\Resources\PrayerRequests\Pages;

use App\Filament\Resources\PrayerRequests\PrayerRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPrayerRequest extends ViewRecord
{
    protected static string $resource = PrayerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit prayer request')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view prayer request');
    }
}
