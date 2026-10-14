<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Members\MemberResource;
use App\Models\Member;
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
            ViewAction::make()->visible(fn() => userCan(Member::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Member::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Member::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Member::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Member::permission('edit'));
    }
}
