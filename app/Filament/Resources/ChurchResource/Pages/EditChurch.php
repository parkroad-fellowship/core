<?php

namespace App\Filament\Resources\ChurchResource\Pages;

use App\Filament\Resources\ChurchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;


class EditChurch extends EditRecord
{
    protected static string $resource = ChurchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view church')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete church')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete church')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore church')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit church');
    }
}
