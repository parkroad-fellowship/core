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
            Actions\EditAction::make()->visible(fn () => userCan('edit mission faq')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view mission faq');
    }
}
