<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Members\MemberResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMember extends EditRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view member')),
            DeleteAction::make()->visible(fn () => userCan('delete member')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete member ')),
            RestoreAction::make()->visible(fn () => userCan('restore member ')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit member');
    }
}
