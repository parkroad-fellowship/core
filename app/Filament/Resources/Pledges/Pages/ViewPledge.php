<?php

namespace App\Filament\Resources\Pledges\Pages;

use App\Filament\Resources\Pledges\PledgeResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPledge extends ViewRecord
{
    protected static string $resource = PledgeResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view pledge');
    }
}
