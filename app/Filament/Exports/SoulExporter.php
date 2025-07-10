<?php

namespace App\Filament\Exports;

use App\Models\Soul;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class SoulExporter extends Exporter
{
    protected static ?string $model = Soul::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('full_name')
                ->label('Full Name'),
            ExportColumn::make('classGroup.name')
                ->label('Class'),
            ExportColumn::make('admission_number')
                ->label('Admission Number'),
            ExportColumn::make('mission.school.name')
                ->label('School Name'),
            ExportColumn::make('created_at')
                ->label('Added On'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your soul export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
