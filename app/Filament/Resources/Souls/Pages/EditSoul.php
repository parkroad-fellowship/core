<?php

namespace App\Filament\Resources\Souls\Pages;

use App\Filament\Resources\Souls\SoulResource;
use App\Models\Soul;
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
            ViewAction::make()->visible(fn() => userCan(Soul::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Soul::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Soul::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Soul::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Soul::permission('edit'));
    }
}
