<?php

namespace App\Filament\Resources\GiftResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\GiftResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGifts extends ListRecords
{
    protected static string $resource = GiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create gift')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny gift');
    }
}
