<?php

namespace App\Filament\Resources\MissionQuestionResource\Pages;

use App\Filament\Resources\MissionQuestionResource;
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
}
