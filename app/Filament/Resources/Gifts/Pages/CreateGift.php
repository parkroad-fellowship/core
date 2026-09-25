<?php

namespace App\Filament\Resources\Gifts\Pages;

use App\Filament\Resources\Gifts\GiftResource;
use App\Models\Gift;
use Filament\Resources\Pages\CreateRecord;

class CreateGift extends CreateRecord
{
    protected static string $resource = GiftResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Gift::permission('create'));
    }
}
