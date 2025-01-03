<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use App\Filament\Resources\SchoolResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchool extends EditRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view school')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete school')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete school')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore school')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit school');
    }
}
