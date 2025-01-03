<?php

namespace App\Filament\Resources\StudentEnquiryResource\Pages;

use App\Filament\Resources\StudentEnquiryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentEnquiry extends EditRecord
{
    protected static string $resource = StudentEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->visible(fn () => userCan('view student enquiry')),
            Actions\DeleteAction::make()->visible(fn () => userCan('delete student enquiry')),
            Actions\ForceDeleteAction::make()->visible(fn () => userCan('forceDelete student enquiry')),
            Actions\RestoreAction::make()->visible(fn () => userCan('restore student enquiry')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit student enquiry');
    }
}
