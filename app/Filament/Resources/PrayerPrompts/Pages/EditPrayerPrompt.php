<?php

namespace App\Filament\Resources\PrayerPrompts\Pages;

use App\Filament\Resources\PrayerPrompts\PrayerPromptResource;
use App\Models\PrayerPrompt;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPrayerPrompt extends EditRecord
{
    protected static string $resource = PrayerPromptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(PrayerPrompt::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(PrayerPrompt::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(PrayerPrompt::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(PrayerPrompt::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(PrayerPrompt::permission('edit'));
    }
}
