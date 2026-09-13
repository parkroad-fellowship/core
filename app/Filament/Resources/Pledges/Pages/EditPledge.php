<?php

namespace App\Filament\Resources\Pledges\Pages;

use App\Filament\Resources\Pledges\PledgeResource;
use Filament\Resources\Pages\EditRecord;

class EditPledge extends EditRecord
{
    protected static string $resource = PledgeResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('update pledge');
    }
}
