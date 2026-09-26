<?php

namespace App\Filament\Resources\MaritalStatuses\Pages;

use App\Filament\Resources\MaritalStatuses\MaritalStatusResource;
use App\Models\MaritalStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMaritalStatus extends EditRecord
{
    protected static string $resource = MaritalStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(MaritalStatus::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(MaritalStatus::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(MaritalStatus::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(MaritalStatus::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MaritalStatus::permission('view'));
    }
}
