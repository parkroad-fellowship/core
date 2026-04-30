<?php

namespace App\Filament\Widgets;

use App\Models\Mission;
use App\Models\MissionType;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MissionTypeBreakdownStats extends BaseWidget
{
    protected static ?int $sort = 18;

    protected function getStats(): array
    {
        $currentYear = now()->year;
        $stats = [];

        $missionTypes = MissionType::query()
            ->withCount(['missions' => function ($query) use ($currentYear) {
                $query->whereYear('start_date', $currentYear);
            }])
            ->get()
            ->filter(fn ($type) => $type->missions_count > 0)
            ->sortByDesc('missions_count')
            ->take(4);

        $colors = ['primary', 'success', 'warning', 'info'];
        $icons = [
            'heroicon-m-academic-cap',
            'heroicon-m-building-office',
            'heroicon-m-home',
            'heroicon-m-globe-alt',
        ];

        foreach ($missionTypes as $index => $missionType) {
            $stats[] = Stat::make($missionType->name, number_format($missionType->missions_count))
                ->description('Missions this year')
                ->descriptionIcon($icons[$index] ?? 'heroicon-m-globe-alt')
                ->color($colors[$index] ?? 'gray');
        }

        // Add total if we have space
        if (count($stats) < 4) {
            $totalMissions = Mission::whereYear('start_date', $currentYear)->count();
            $stats[] = Stat::make('Total Missions', number_format($totalMissions))
                ->description('All types this year')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success');
        }

        return $stats;
    }
}
