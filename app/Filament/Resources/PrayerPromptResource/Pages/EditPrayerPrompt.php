<?php

namespace App\Filament\Resources\PrayerPromptResource\Pages;

use App\Filament\Resources\PrayerPromptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;



class EditPrayerPrompt extends EditRecord
{
    protected static string $resource = PrayerPromptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view prayer prompt')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete prayer prompt')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete prayer prompt')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore prayer prompt')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit prayer prompt');
    }

}