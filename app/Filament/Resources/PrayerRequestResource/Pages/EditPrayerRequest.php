<?php

namespace App\Filament\Resources\PrayerRequestResource\Pages;

use App\Filament\Resources\PrayerRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrayerRequest extends EditRecord
{
    protected static string $resource = PrayerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view prayer request')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete prayer request')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('force delete prayer request')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore prayer request')),
        ];
    }
}
