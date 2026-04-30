<?php

namespace App\Filament\Resources\Churches\Pages;

use App\Filament\Resources\Churches\ChurchResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditChurch extends EditRecord
{
    protected static string $resource = ChurchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view church')),
            DeleteAction::make()->visible(fn () => userCan('delete church')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete church')),
            RestoreAction::make()->visible(fn () => userCan('restore church')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit church');
    }
}
