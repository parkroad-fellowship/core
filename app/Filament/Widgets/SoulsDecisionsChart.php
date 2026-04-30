<?php

namespace App\Filament\Widgets;

use App\Enums\PRFSoulDecisionType;
use App\Models\Soul;
use Filament\Widgets\ChartWidget;

class SoulsDecisionsChart extends ChartWidget
{
    protected ?string $heading = 'Soul Decisions Over Time';

    protected static ?int $sort = 6;

    protected function getData(): array
    {
        $months = collect();
        $salvationData = collect();
        $redededicationData = collect();
        $otherData = collect();

        // Get data for the last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push($date->format('M Y'));

            $salvationData->push(
                Soul::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('decision_type', PRFSoulDecisionType::SALVATION->value)
                    ->count()
            );

            $redededicationData->push(
                Soul::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('decision_type', PRFSoulDecisionType::REDEDICATION->value)
                    ->count()
            );

            $otherData->push(
                Soul::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->whereNotIn('decision_type', [
                        PRFSoulDecisionType::SALVATION->value,
                        PRFSoulDecisionType::REDEDICATION->value,
                    ])
                    ->count()
            );
        }

        return [
            'datasets' => [
                [
                    'label' => 'Salvation',
                    'data' => $salvationData->toArray(),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'fill' => true,
                ],
                [
                    'label' => 'Rededication',
                    'data' => $redededicationData->toArray(),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'fill' => true,
                ],
                [
                    'label' => 'Other Decisions',
                    'data' => $otherData->toArray(),
                    'borderColor' => 'rgb(234, 179, 8)',
                    'backgroundColor' => 'rgba(234, 179, 8, 0.2)',
                    'fill' => true,
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
