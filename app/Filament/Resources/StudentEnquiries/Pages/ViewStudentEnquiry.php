<?php

namespace App\Filament\Resources\StudentEnquiries\Pages;

use App\Filament\Resources\StudentEnquiries\StudentEnquiryResource;
use App\Models\StudentEnquiry;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentEnquiry extends ViewRecord
{
    protected static string $resource = StudentEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn() => userCan(StudentEnquiry::permission('edit'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(StudentEnquiry::permission('view'));
    }
}
