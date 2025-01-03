<?php

namespace App\Filament\Resources\CohortResource\Pages;

use App\Filament\Resources\CohortResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;



class ListCohorts extends ListRecords
{
    protected static string $resource = CohortResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(fn () => userCan('create cohort')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny cohort');
    }
}
