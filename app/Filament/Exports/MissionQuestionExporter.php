<?php

namespace App\Filament\Exports;

use App\Models\MissionQuestion;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class MissionQuestionExporter extends Exporter
{
    protected static ?string $model = MissionQuestion::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('mission.school.name')
                ->label('School Name'),
            ExportColumn::make('mission.start_date')
                ->label('Mission Start Date'),
            ExportColumn::make('question')
                ->label('Question Text'),
            ExportColumn::make('created_at')
                ->label('Created At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your mission question export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
