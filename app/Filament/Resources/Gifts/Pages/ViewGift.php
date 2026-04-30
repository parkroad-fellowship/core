<?php

namespace App\Filament\Resources\Gifts\Pages;

use App\Filament\Resources\Gifts\GiftResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGift extends ViewRecord
{
    protected static string $resource = GiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit gift')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view gift');
    }
}
