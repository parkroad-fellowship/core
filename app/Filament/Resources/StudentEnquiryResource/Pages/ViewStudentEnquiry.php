<?php

namespace App\Filament\Resources\StudentEnquiryResource\Pages;

use App\Filament\Resources\StudentEnquiryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentEnquiry extends ViewRecord
{
    protected static string $resource = StudentEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
