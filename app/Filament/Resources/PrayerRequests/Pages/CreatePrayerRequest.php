<?php

namespace App\Filament\Resources\PrayerRequests\Pages;

use App\Filament\Resources\PrayerRequests\PrayerRequestResource;
use App\Models\PrayerRequest;
use Filament\Resources\Pages\CreateRecord;

class CreatePrayerRequest extends CreateRecord
{
    protected static string $resource = PrayerRequestResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(PrayerRequest::permission('create'));
    }
}
