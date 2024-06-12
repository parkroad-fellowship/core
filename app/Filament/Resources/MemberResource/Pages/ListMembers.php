<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->can('editmember')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('deletemember')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDeletemember')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restoremember')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('{permission}');
    }
}
