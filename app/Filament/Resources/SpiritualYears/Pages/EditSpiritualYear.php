<?php

namespace App\Filament\Resources\SpiritualYears\Pages;

use App\Filament\Resources\SpiritualYears\SpiritualYearResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
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
