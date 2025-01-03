<?php

namespace App\Filament\Resources\MembershipResource\Pages;

use App\Filament\Resources\MembershipResource;
use Filament\Resources\Pages\CreateRecord;



class CreateMembership extends CreateRecord
{
    protected static string $resource = MembershipResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create membership');
    }
}
