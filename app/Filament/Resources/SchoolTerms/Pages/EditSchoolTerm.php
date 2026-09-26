<?php

namespace App\Filament\Resources\SchoolTerms\Pages;

use App\Filament\Resources\SchoolTerms\SchoolTermResource;
use App\Models\SchoolTerm;
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
            ViewAction::make()->visible(fn() => userCan(SchoolTerm::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(SchoolTerm::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(SchoolTerm::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(SchoolTerm::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(SchoolTerm::permission('edit'));
    }
}
