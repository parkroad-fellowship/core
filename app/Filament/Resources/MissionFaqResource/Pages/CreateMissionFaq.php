<?php

namespace App\Filament\Resources\MissionFaqResource\Pages;

use App\Filament\Resources\MissionFaqResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMissionFaq extends CreateRecord
{
    protected static string $resource = MissionFaqResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create mission faq');
    }
}
