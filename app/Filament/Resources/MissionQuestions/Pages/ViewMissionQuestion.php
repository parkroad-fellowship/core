<?php

namespace App\Filament\Resources\MissionQuestions\Pages;

use App\Filament\Resources\MissionQuestions\MissionQuestionResource;
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

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view mission question');
    }
}
