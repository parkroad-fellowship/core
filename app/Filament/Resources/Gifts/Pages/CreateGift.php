<?php

namespace App\Filament\Resources\Gifts\Pages;

use App\Filament\Resources\Gifts\GiftResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGift extends CreateRecord
{
    protected static string $resource = GiftResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create gift');
    }
}
