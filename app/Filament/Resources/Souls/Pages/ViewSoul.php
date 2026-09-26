<?php

namespace App\Filament\Resources\Souls\Pages;

use App\Filament\Resources\Souls\SoulResource;
use App\Models\Soul;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSoul extends ViewRecord
{
    protected static string $resource = SoulResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn() => userCan(Soul::permission('edit'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Soul::permission('view'));
    }
}
