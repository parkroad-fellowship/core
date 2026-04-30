<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Members\MemberResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMember extends ViewRecord
{
    use HasAlpineRelationManagerTabs;

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
