<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMember extends ViewRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn () => auth()->can('edit member')),
            Actions\DeleteAction::make()->visible(fn () => auth()->can('delete member')),
            Actions\ForceDeleteAction::make()->visible(fn () => auth()->can('forceDelete member')),
            Actions\RestoreAction::make()->visible(fn () => auth()->can('restore member}')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('view member');
    }
}
