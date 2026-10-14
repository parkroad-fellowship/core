<?php

namespace App\Filament\Resources\SpiritualYears\Pages;

use App\Filament\Resources\SpiritualYears\SpiritualYearResource;
use App\Models\SpiritualYear;
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
            ViewAction::make()->visible(fn() => userCan(SpiritualYear::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(SpiritualYear::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(SpiritualYear::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(SpiritualYear::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(SpiritualYear::permission('edit'));
    }
}
