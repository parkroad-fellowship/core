<?php

namespace App\Filament\Resources\GiftResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\GiftResource;
use Filament\Actions;
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
