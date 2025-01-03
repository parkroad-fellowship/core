<?php

namespace App\Filament\Resources\SchoolTermResource\Pages;

use App\Filament\Resources\SchoolTermResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchoolTerm extends EditRecord
{
    protected static string $resource = SchoolTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view school term')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete school term')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete school term')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore school term')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit school term');
    }
}
