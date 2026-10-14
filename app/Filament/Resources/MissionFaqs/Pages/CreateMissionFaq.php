<?php

namespace App\Filament\Resources\MissionFaqs\Pages;

use App\Filament\Resources\MissionFaqs\MissionFaqResource;
use App\Models\MissionFaq;
use Filament\Resources\Pages\CreateRecord;

class CreateMissionFaq extends CreateRecord
{
    protected static string $resource = MissionFaqResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MissionFaq::permission('create'));
    }
}
