<?php

namespace App\Filament\Resources\Souls\Pages;

use App\Filament\Resources\Souls\SoulResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSoul extends EditRecord
{
    protected static string $resource = SoulResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view soul')),
            DeleteAction::make()->visible(fn () => userCan('delete soul')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete soul')),
            RestoreAction::make()->visible(fn () => userCan('restore soul')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit soul');
    }
}
