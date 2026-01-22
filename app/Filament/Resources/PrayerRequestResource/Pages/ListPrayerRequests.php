<?php

namespace App\Filament\Resources\PrayerRequestResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PrayerRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrayerRequests extends ListRecords
{
    protected static string $resource = PrayerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create prayer request')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny prayer request');
    }
}
