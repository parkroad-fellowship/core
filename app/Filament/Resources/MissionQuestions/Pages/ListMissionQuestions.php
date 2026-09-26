<?php

namespace App\Filament\Resources\MissionQuestions\Pages;

use App\Filament\Resources\MissionQuestions\MissionQuestionResource;
use App\Models\MissionQuestion;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMissionQuestions extends ListRecords
{
    protected static string $resource = MissionQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MissionQuestion::permission('viewAny'));
    }
}
