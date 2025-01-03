<?php

namespace App\Filament\Resources\SoulResource\Pages;

use App\Filament\Resources\SoulResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSoul extends EditRecord
{
    protected static string $resource = SoulResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view soul')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete soul')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete soul')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore soul')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit soul');
    }
}
