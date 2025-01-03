<?php

namespace App\Filament\Resources\MissionQuestionResource\Pages;

use App\Filament\Resources\MissionQuestionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMissionQuestion extends CreateRecord
{
    protected static string $resource = MissionQuestionResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create mission question');
    }
}
