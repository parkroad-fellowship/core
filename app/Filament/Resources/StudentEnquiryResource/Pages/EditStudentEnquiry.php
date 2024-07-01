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
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
