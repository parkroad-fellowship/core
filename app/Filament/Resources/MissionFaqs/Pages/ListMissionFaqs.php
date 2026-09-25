<?php

namespace App\Filament\Resources\MissionFaqs\Pages;

use App\Filament\Resources\MissionFaqs\MissionFaqResource;
use App\Models\MissionFaq;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMissionFaqs extends ListRecords
{
    protected static string $resource = MissionFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn() => userCan(MissionFaq::permission('create'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MissionFaq::permission('viewAny'));
    }
}
