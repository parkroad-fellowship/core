<?php

namespace App\Filament\Resources\Professions\Pages;

use App\Filament\Resources\Professions\ProfessionResource;
use App\Models\Profession;
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
            ViewAction::make()->visible(fn() => userCan(Profession::permission('create'))),
            DeleteAction::make()->visible(fn() => userCan(Profession::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Profession::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Profession::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Profession::permission('edit'));
    }
}
