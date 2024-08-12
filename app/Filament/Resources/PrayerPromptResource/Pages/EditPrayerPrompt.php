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
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
