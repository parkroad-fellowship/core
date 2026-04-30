<?php

namespace App\Filament\Resources\MissionQuestions\Pages;

use App\Filament\Resources\MissionQuestions\MissionQuestionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMissionQuestion extends CreateRecord
{
    protected static string $resource = MissionQuestionResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create mission question');
    }
}
