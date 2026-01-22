<?php

namespace App\Filament\Resources\MissionFaqResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\MissionFaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMissionFaq extends ViewRecord
{
    protected static string $resource = MissionFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit mission faq')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view mission faq');
    }
}
