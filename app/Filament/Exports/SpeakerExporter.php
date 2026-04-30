<?php

namespace App\Filament\Exports;

use App\Models\Speaker;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class SpeakerExporter extends Exporter
{
    protected static ?string $model = Speaker::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('Speaker Name'),
            ExportColumn::make('phone_number')
                ->label('Phone Number'),
            ExportColumn::make('title')
                ->label('Job Title'),
            ExportColumn::make('bio')
                ->label('Biography'),
            ExportColumn::make('eventSpeakers_count')
                ->label('Number of Events')
                ->state(function (Speaker $record): int {
                    return $record->eventSpeakers()->count();
                }),
            ExportColumn::make('latest_event')
                ->label('Latest Event')
                ->state(function (Speaker $record): ?string {
                    $latestEventSpeaker = $record->eventSpeakers()
                        ->with('event')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    return $latestEventSpeaker?->event?->name ?? 'No events';
                }),
            ExportColumn::make('created_at')
                ->label('Date Added'),
            ExportColumn::make('updated_at')
                ->label('Last Updated'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your speaker export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
