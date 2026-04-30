<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use Filament\Widgets\ChartWidget;

class DepartmentDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Members by Department';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $departments = Department::query()
            ->withCount('members')
            ->get()
            ->filter(fn ($dept) => $dept->members_count > 0)
            ->sortByDesc('members_count')
            ->take(8);

        $labels = [];
        $data = [];

        foreach ($departments as $department) {
            $labels[] = strlen($department->name) > 20
                ? substr($department->name, 0, 17).'...'
                : $department->name;
            $data[] = $department->members_count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Members',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)',
                        'rgb(234, 179, 8)',
                        'rgb(239, 68, 68)',
                        'rgb(168, 85, 247)',
                        'rgb(20, 184, 166)',
                        'rgb(249, 115, 22)',
                        'rgb(236, 72, 153)',
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
