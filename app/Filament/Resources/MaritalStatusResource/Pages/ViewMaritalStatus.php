<?php

namespace App\Filament\Resources\MaritalStatusResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\MaritalStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMaritalStatus extends ViewRecord
{
    protected static string $resource = MaritalStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('create marital status')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view marital status');
    }
}
