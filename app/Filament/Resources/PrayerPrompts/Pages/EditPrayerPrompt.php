<?php

namespace App\Filament\Resources\PrayerPrompts\Pages;

use App\Filament\Resources\PrayerPrompts\PrayerPromptResource;
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
            ViewAction::make()->visible(fn () => userCan('view prayer prompt')),
            DeleteAction::make()->visible(fn () => userCan('delete prayer prompt')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete prayer prompt')),
            RestoreAction::make()->visible(fn () => userCan('restore prayer prompt')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit prayer prompt');
    }
}
