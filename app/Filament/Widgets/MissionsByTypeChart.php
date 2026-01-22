<?php

namespace App\Filament\Widgets;

use App\Models\Mission;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MissionsByTypeChart extends ChartWidget
{
    protected ?string $heading = 'Missions by Type';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $missionData = Mission::select('mission_type_id', DB::raw('count(*) as total'))
            ->whereNotNull('mission_type_id')
            ->groupBy('mission_type_id')
            ->with('missionType')
            ->get();

        $labels = [];
        $data = [];

        foreach ($missionData as $item) {
            $labels[] = $item->missionType->name ?? 'Unknown';
            $data[] = $item->total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Missions',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgb(99, 255, 132)',
                        'rgb(54, 162, 235)',
                        'rgb(255, 99, 86)',
                        'rgb(192, 75, 192)',
                        'rgb(102, 153, 255)',
                        'rgb(159, 255, 64)',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
