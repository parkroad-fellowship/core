<?php

namespace App\Filament\Resources\StudentEnquiries\Pages;

use App\Filament\Resources\StudentEnquiries\StudentEnquiryResource;
use App\Models\StudentEnquiry;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentEnquiry extends CreateRecord
{
    protected static string $resource = StudentEnquiryResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(StudentEnquiry::permission('create'));
    }
}
