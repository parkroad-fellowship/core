<?php

namespace App\Filament\Exports;

use App\Enums\PRFPledgeFrequency;
use App\Enums\PRFPledgeStatus;
use App\Models\Pledge;
use Carbon\CarbonInterface;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PledgeExporter extends Exporter
{
    protected static ?string $model = Pledge::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Name')->preventFormulaInjection(),
            ExportColumn::make('member.full_name')->label('Linked Member')->preventFormulaInjection(),
            ExportColumn::make('phone')->label('WhatsApp')->preventFormulaInjection(),
            ExportColumn::make('email')->label('Email')->preventFormulaInjection(),
            ExportColumn::make('amount')->label('Amount (KES)'),
            ExportColumn::make('frequency')
                ->label('Frequency')
                ->formatStateUsing(fn(mixed $state): string => $state instanceof PRFPledgeFrequency
                    ? $state->getLabel()
                    : PRFPledgeFrequency::tryFrom((int) $state)?->getLabel() ?? (string) $state),
            ExportColumn::make('installments_sum_amount')
                ->label('Fulfilled To Date (KES)')
                ->sum('installments', 'amount')
                ->formatStateUsing(fn(mixed $state): int => (int) $state),
            ExportColumn::make('installments_count')->label('Installments')->counts('installments'),
            ExportColumn::make('start_date')
                ->label('Start Date')
                ->formatStateUsing(fn(?CarbonInterface $state): ?string => $state?->format('Y-m-d')),
            ExportColumn::make('next_due_on')
                ->label('Next Due')
                ->formatStateUsing(fn(?CarbonInterface $state): ?string => $state?->format('Y-m-d')),
            ExportColumn::make('last_fulfilled_on')
                ->label('Last Fulfilled')
                ->formatStateUsing(fn(?CarbonInterface $state): ?string => $state?->format('Y-m-d')),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn(mixed $state): string => $state instanceof PRFPledgeStatus
                    ? $state->getLabel()
                    : PRFPledgeStatus::tryFrom((int) $state)?->getLabel() ?? (string) $state),
            ExportColumn::make('created_at')
                ->label('Submitted On')
                ->formatStateUsing(fn(?CarbonInterface $state): ?string => $state?->format('Y-m-d H:i')),
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

    public function getFileName(Export $export): string
    {
        return 'pledge-export-' . now()->format('Y-m-d-H-i-s');
    }
}
