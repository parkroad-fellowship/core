<?php

namespace App\Filament\Resources\Professions\Pages;

use App\Filament\Resources\Professions\ProfessionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProfession extends EditRecord
{
    protected static string $resource = ProfessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('create profession')),
            DeleteAction::make()->visible(fn () => userCan('delete profession')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete profession')),
            RestoreAction::make()->visible(fn () => userCan('restore profession')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit profession');
    }
}
