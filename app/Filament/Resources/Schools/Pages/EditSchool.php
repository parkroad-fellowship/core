<?php

namespace App\Filament\Resources\Schools\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Schools\SchoolResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSchool extends EditRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view school')),
            DeleteAction::make()->visible(fn () => userCan('delete school')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete school')),
            RestoreAction::make()->visible(fn () => userCan('restore school')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit school');
    }
}
