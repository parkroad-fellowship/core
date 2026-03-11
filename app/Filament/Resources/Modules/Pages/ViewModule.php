<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Modules\ModuleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewModule extends ViewRecord
{
    use HasAlpineRelationManagerTabs;

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
