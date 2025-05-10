<?php

namespace App\Filament\Resources\PrayerRequestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\PrayerRequestResource;

class ViewPrayerRequest extends ViewRecord
{
    protected static string $resource = PrayerRequestResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn() => userCan('edit prayer request')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view prayer request');
    }
}
