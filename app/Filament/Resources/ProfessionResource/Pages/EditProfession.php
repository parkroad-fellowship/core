<?php

namespace App\Filament\Resources\ProfessionResource\Pages;

use App\Filament\Resources\ProfessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProfession extends EditRecord
{
    protected static string $resource = ProfessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('create profession')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete profession')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete profession')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore profession')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit profession');
    }
}
