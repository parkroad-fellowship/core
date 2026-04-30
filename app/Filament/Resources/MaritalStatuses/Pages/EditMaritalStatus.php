<?php

namespace App\Filament\Resources\MaritalStatuses\Pages;

use App\Filament\Resources\MaritalStatuses\MaritalStatusResource;
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
            ViewAction::make()->visible(fn () => userCan('view marital status')),
            DeleteAction::make()->visible(fn () => userCan('delete marital status')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete marital status')),
            RestoreAction::make()->visible(fn () => userCan('restore marital status')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view marital status');
    }
}
