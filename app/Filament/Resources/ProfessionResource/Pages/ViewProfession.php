<?php

namespace App\Filament\Resources\ProfessionResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ProfessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProfession extends ViewRecord
{
    protected static string $resource = ProfessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit profession')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view profession');
    }
}
