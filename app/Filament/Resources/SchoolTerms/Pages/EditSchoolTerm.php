<?php

namespace App\Filament\Resources\SchoolTerms\Pages;

use App\Filament\Resources\SchoolTerms\SchoolTermResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSchoolTerm extends EditRecord
{
    protected static string $resource = SchoolTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view school term')),
            DeleteAction::make()->visible(fn () => userCan('delete school term')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete school term')),
            RestoreAction::make()->visible(fn () => userCan('restore school term')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit school term');
    }
}
