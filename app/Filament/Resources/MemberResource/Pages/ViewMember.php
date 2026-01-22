<?php

namespace App\Filament\Resources\MemberResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMember extends ViewRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit member')),

        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view member');
    }
}
