<?php

namespace App\Filament\Resources\Pledges\Pages;

use App\Filament\Resources\Pledges\PledgeResource;
use App\Filament\Resources\Pledges\Widgets\PledgeStatsOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPledges extends ListRecords
{
    protected static string $resource = PledgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make()->visible(fn() => userCan('create pledge')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PledgeStatsOverview::class,
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny pledge');
    }
}
