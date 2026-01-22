<?php

namespace App\Filament\Resources\ClassGroupResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ClassGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClassGroup extends ViewRecord
{
    protected static string $resource = ClassGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit class group')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view class group');
    }
}
