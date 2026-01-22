<?php

namespace App\Filament\Resources\GroupResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\GroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGroup extends ViewRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit group')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view group');
    }
}
