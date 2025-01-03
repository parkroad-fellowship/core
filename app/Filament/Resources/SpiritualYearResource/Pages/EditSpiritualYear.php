<?php

namespace App\Filament\Resources\SpiritualYearResource\Pages;

use App\Filament\Resources\SpiritualYearResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSpiritualYear extends EditRecord
{
    protected static string $resource = SpiritualYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view spiritual year')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete spiritual year')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete spiritual year')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore spiritual year')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit spiritual year');
    }
}
