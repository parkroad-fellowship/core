<?php

namespace App\Filament\Resources\GiftResource\Pages;

use App\Filament\Resources\GiftResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGift extends ViewRecord
{
    protected static string $resource = GiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(fn () => auth()->user()->can('create gift')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny gift');
    }
}
