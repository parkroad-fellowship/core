<?php

namespace App\Filament\Resources\StudentEnquiries\Pages;

use App\Filament\Resources\StudentEnquiries\StudentEnquiryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentEnquiry extends ViewRecord
{
    protected static string $resource = StudentEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn () => userCan('edit student enquiry')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('view student enquiry');
    }
}
