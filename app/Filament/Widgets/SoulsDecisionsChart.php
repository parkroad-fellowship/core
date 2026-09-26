<?php

namespace App\Filament\Widgets;

use App\Enums\PRFSoulDecisionType;
use App\Filament\Support\PRFPalette;
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
                    ->where('decision_type', PRFSoulDecisionType::SALVATION)
                    ->count(),
            );

            $redededicationData->push(
                Soul::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('decision_type', PRFSoulDecisionType::REDEDICATION)
                    ->count(),
            );

            $otherData->push(
                Soul::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->whereNotIn('decision_type', [
                        PRFSoulDecisionType::SALVATION,
                        PRFSoulDecisionType::REDEDICATION,
                    ])
                    ->count(),
            );
        }

        return [
            'datasets' => [
                [
                    'label' => 'Salvation',
                    'data' => $salvationData->toArray(),
                    'borderColor' => PRFPalette::SUCCESS,
                    'backgroundColor' => PRFPalette::chart(PRFPalette::SUCCESS, 0.2),
                    'fill' => true,
                ],
                [
                    'label' => 'Rededication',
                    'data' => $redededicationData->toArray(),
                    'borderColor' => PRFPalette::INFO,
                    'backgroundColor' => PRFPalette::chart(PRFPalette::INFO, 0.2),
                    'fill' => true,
                ],
                [
                    'label' => 'Other Decisions',
                    'data' => $otherData->toArray(),
                    'borderColor' => PRFPalette::WARNING,
                    'backgroundColor' => PRFPalette::chart(PRFPalette::WARNING, 0.2),
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
