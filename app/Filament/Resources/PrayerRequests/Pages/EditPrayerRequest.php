<?php

namespace App\Filament\Resources\PrayerRequests\Pages;

use App\Filament\Resources\PrayerRequests\PrayerRequestResource;
use App\Models\PrayerRequest;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPrayerRequest extends EditRecord
{
    protected static string $resource = PrayerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(PrayerRequest::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(PrayerRequest::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(PrayerRequest::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(PrayerRequest::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(PrayerRequest::permission('edit'));
    }
}
