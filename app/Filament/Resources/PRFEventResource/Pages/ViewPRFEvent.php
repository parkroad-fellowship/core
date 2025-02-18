<?php

namespace App\Filament\Resources\PRFEventResource\Pages;

use App\Filament\Resources\PRFEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPRFEvent extends ViewRecord
{
    protected static string $resource = PRFEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view event');
    }
}
