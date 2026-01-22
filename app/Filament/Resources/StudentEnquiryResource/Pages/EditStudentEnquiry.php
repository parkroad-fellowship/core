<?php

namespace App\Filament\Resources\StudentEnquiryResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\StudentEnquiryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentEnquiry extends EditRecord
{
    protected static string $resource = StudentEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn () => userCan('view student enquiry')),
            DeleteAction::make()->visible(fn () => userCan('delete student enquiry')),
            ForceDeleteAction::make()->visible(fn () => userCan('forceDelete student enquiry')),
            RestoreAction::make()->visible(fn () => userCan('restore student enquiry')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit student enquiry');
    }
}
