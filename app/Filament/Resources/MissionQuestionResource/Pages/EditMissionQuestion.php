<?php

namespace App\Filament\Resources\MissionQuestionResource\Pages;

use App\Filament\Resources\MissionQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMissionQuestion extends EditRecord
{
    protected static string $resource = MissionQuestionResource::class;

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
