<?php

namespace App\Filament\Resources\PRFEvents\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\PRFEvents\PRFEventResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPRFEvent extends ViewRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = PRFEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view event');
    }
}
