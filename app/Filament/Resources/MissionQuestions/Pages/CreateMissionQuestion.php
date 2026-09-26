<?php

namespace App\Filament\Resources\MissionQuestions\Pages;

use App\Filament\Resources\MissionQuestions\MissionQuestionResource;
use App\Models\MissionQuestion;
use Filament\Resources\Pages\CreateRecord;

class CreateMissionQuestion extends CreateRecord
{
    protected static string $resource = MissionQuestionResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MissionQuestion::permission('create'));
    }
}
