<?php

namespace App\Filament\Resources\ModuleResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewModule extends ViewRecord
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit module')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view module');
    }
}
