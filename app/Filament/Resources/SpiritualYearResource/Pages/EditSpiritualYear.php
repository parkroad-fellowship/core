<?php

namespace App\Filament\Resources\SpiritualYearResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\SpiritualYearResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSpiritualYear extends EditRecord
{
    protected static string $resource = SpiritualYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view spiritual year')),
            DeleteAction::make()->visible(fn () => userCan('delete spiritual year')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete spiritual year')),
            RestoreAction::make()->visible(fn () => userCan('restore spiritual year')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit spiritual year');
    }
}
