<?php

namespace App\Filament\Resources\MaritalStatuses\Pages;

use App\Filament\Resources\MaritalStatuses\MaritalStatusResource;
use App\Models\MaritalStatus;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMaritalStatus extends ViewRecord
{
    protected static string $resource = MaritalStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn() => userCan(MaritalStatus::permission('create'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MaritalStatus::permission('view'));
    }
}
