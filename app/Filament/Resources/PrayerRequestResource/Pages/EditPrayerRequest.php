<?php

namespace App\Filament\Resources\PrayerRequestResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\PrayerRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrayerRequest extends EditRecord
{
    protected static string $resource = PrayerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view prayer request')),
            DeleteAction::make()->visible(fn () => userCan('delete prayer request')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete prayer request')),
            RestoreAction::make()->visible(fn () => userCan('restore prayer request')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit prayer request');
    }
}
