<?php

namespace App\Filament\Resources\MissionFaqResource\Pages;

use App\Filament\Resources\MissionFaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMissionFaq extends ViewRecord
{
    protected static string $resource = MissionFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
