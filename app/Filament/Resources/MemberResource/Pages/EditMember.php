<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => auth()->user()->can('view member')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete member')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->user()->can('forceDelete member ')),
            Actions\RestoreAction::make()->visible(fn () => auth()->user()->can('restore member ')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('edit member');
    }
}
