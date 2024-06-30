<?php

namespace App\Filament\Resources\MissionQuestionResource\Pages;

use App\Filament\Resources\MissionQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMissionQuestion extends ViewRecord
{
    protected static string $resource = MissionQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }
}
