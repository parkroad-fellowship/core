<?php

namespace App\Filament\Resources\PrayerRequests\Pages;

use App\Filament\Resources\PrayerRequests\PrayerRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePrayerRequest extends CreateRecord
{
    protected static string $resource = PrayerRequestResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create prayer request');
    }
}
