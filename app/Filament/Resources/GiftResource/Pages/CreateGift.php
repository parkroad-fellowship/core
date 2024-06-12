<?php

namespace App\Filament\Resources\GiftResource\Pages;

use App\Filament\Resources\GiftResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGift extends CreateRecord
{
    protected static string $resource = GiftResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
