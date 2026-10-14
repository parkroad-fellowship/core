<?php

namespace App\Filament\Resources\StudentEnquiries\Pages;

use App\Filament\Resources\StudentEnquiries\StudentEnquiryResource;
use App\Models\StudentEnquiry;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditStudentEnquiry extends EditRecord
{
    protected static string $resource = StudentEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(StudentEnquiry::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(StudentEnquiry::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(StudentEnquiry::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(StudentEnquiry::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(StudentEnquiry::permission('edit'));
    }
}
