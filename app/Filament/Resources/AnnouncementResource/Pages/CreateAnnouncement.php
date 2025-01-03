<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use Filament\Resources\Pages\CreateRecord;



class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create announcement');
    }
}
