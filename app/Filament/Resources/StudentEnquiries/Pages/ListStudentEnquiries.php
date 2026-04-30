<?php

namespace App\Filament\Resources\StudentEnquiries\Pages;

use App\Filament\Resources\StudentEnquiries\StudentEnquiryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentEnquiries extends ListRecords
{
    protected static string $resource = StudentEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny student enquiry');
    }
}
