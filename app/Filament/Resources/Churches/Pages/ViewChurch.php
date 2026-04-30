<?php

namespace App\Filament\Resources\Churches\Pages;

use App\Filament\Resources\Churches\ChurchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewChurch extends ViewRecord
{
    protected static string $resource = ChurchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit church')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view church');
    }
}
