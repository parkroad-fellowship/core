<?php

namespace App\Filament\Resources\StudentEnquiries\Pages;

use App\Filament\Resources\StudentEnquiries\StudentEnquiryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentEnquiry extends CreateRecord
{
    protected static string $resource = StudentEnquiryResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('create student enquiry');
    }
}
