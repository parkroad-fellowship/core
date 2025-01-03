<?php

namespace App\Filament\Resources\MaritalStatusResource\Pages;

use App\Filament\Resources\MaritalStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaritalStatus extends EditRecord
{
    protected static string $resource = MaritalStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view marital status')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete marital status')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete marital status')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore marital status')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view marital status');
    }
}
