<?php

namespace App\Filament\Widgets;

use App\Filament\Support\PRFPalette;
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
            ->filter(fn($dept) => $dept->members_count > 0)
            ->sortByDesc('members_count')
            ->take(8);

        $labels = [];
        $data = [];

        foreach ($departments as $department) {
            $labels[] = strlen($department->name) > 20 ? substr($department->name, 0, 17) . '...' : $department->name;
            $data[] = $department->members_count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Members',
                    'data' => $data,
                    'backgroundColor' => PRFPalette::SERIES,
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
