<?php

namespace App\Filament\Exports;

use App\Enums\PRFPledgeFrequency;
use App\Enums\PRFPledgeStatus;
use App\Models\Pledge;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PledgeExporter extends Exporter
{
    protected static ?string $model = Pledge::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Name'),
            ExportColumn::make('phone')->label('WhatsApp'),
            ExportColumn::make('email')->label('Email'),
            ExportColumn::make('amount')->label('Amount'),
            ExportColumn::make('frequency')
                ->label('Frequency')
                ->formatStateUsing(fn(mixed $state): string => $state?->getLabel() ?? (string) $state),
            ExportColumn::make('start_date')->label('Start Date'),
            ExportColumn::make('next_due_on')->label('Next Due'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(
                    fn(mixed $state): string => PRFPledgeStatus::tryFrom((int) $state)?->getLabel() ?? (string) $state,
                ),
            ExportColumn::make('created_at')->label('Submitted On'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body =
            'Your pledge export has completed and '
            . number_format($export->successful_rows)
            . ' '
            . str('row')->plural($export->successful_rows)
            . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .=
                ' '
                . number_format($failedRowsCount)
                . ' '
                . str('row')->plural($failedRowsCount)
                . ' failed to export.';
        }

        return $body;
    }
}
