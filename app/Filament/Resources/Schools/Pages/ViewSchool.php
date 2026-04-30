<?php

namespace App\Filament\Resources\Schools\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Schools\SchoolResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSchool extends ViewRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit school')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view school');
    }
}
