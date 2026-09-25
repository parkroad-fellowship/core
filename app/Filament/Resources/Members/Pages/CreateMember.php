<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Resources\Members\MemberResource;
use App\Jobs\Member\OnboardJob;
use App\Models\Member;
use Filament\Resources\Pages\CreateRecord;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function afterCreate(): void
    {
        OnboardJob::dispatchSync($this->getRecord());
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Member::permission('create'));
    }
}
