<?php

namespace App\Filament\Widgets;

use App\Enums\PRFMissionStatus;
use App\Filament\Support\PRFPalette;
use App\Models\Mission;
use Filament\Widgets\ChartWidget;

class MissionPipelineWidget extends ChartWidget
{
    protected ?string $heading = 'Mission Pipeline';

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $currentYear = now()->year;

        $pending = Mission::where('status', PRFMissionStatus::PENDING)->whereYear('start_date', $currentYear)->count();

        $approved = Mission::whereIn('status', [
            PRFMissionStatus::APPROVED,
            PRFMissionStatus::FULLY_SUBSCRIBED,
        ])
            ->whereYear('start_date', $currentYear)
            ->count();

        $serviced = Mission::where('status', PRFMissionStatus::SERVICED)
            ->whereYear('start_date', $currentYear)
            ->count();

        $rejected = Mission::where('status', PRFMissionStatus::REJECTED)
            ->whereYear('start_date', $currentYear)
            ->count();

        $cancelled = Mission::where('status', PRFMissionStatus::CANCELLED)
            ->whereYear('start_date', $currentYear)
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Missions',
                    'data' => [$pending, $approved, $serviced, $rejected, $cancelled],
                    'backgroundColor' => [
                        PRFPalette::WARNING, // Pending
                        PRFPalette::SUCCESS, // Approved
                        PRFPalette::INFO, // Serviced
                        PRFPalette::DANGER, // Rejected
                        PRFPalette::NEUTRAL, // Cancelled
                    ],
                ],
            ],
            'labels' => ['Pending', 'Approved', 'Serviced', 'Rejected', 'Cancelled'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
