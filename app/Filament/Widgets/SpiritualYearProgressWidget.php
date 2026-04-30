<?php

namespace App\Filament\Widgets;

use App\Models\Membership;
use App\Models\SpiritualYear;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SpiritualYearProgressWidget extends BaseWidget
{
    protected static ?int $sort = 24;

    protected function getStats(): array
    {
        $stats = [];

        $spiritualYears = SpiritualYear::query()
            ->withCount(['memberships' => function ($query) {
                $query->where('approved', true);
            }])
            ->orderByDesc('memberships_count')
            ->limit(4)
            ->get();

        $colors = ['success', 'primary', 'warning', 'info'];
        $icons = [
            'heroicon-m-star',
            'heroicon-m-academic-cap',
            'heroicon-m-arrow-trending-up',
            'heroicon-m-user-group',
        ];

        foreach ($spiritualYears as $index => $year) {
            $stats[] = Stat::make($year->name, number_format($year->memberships_count))
                ->description('Active members')
                ->descriptionIcon($icons[$index] ?? 'heroicon-m-users')
                ->color($colors[$index] ?? 'gray');
        }

        // If we don't have 4 spiritual years, add a total
        if (count($stats) < 4) {
            $totalMemberships = Membership::where('approved', true)->count();
            $stats[] = Stat::make('Total Memberships', number_format($totalMemberships))
                ->description('All spiritual years')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success');
        }

        return $stats;
    }
}
