<?php

namespace App\Filament\Resources\Pledges\Widgets;

use App\Models\Pledge;
use App\Models\PledgeInstallment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PledgeStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $count = Pledge::query()->count();

        $projectedAnnual = (float) Pledge::query()->get()->sum(fn(Pledge $pledge) => $pledge->annualizedAmount());

        $fulfilledThisYear = (float) PledgeInstallment::query()->whereYear('fulfilled_on', now()->year)->sum('amount');

        $dueSoon = Pledge::query()
            ->whereNotNull('next_due_on')
            ->whereBetween('next_due_on', [now()->toDateString(), now()->addDays(14)->toDateString()])
            ->count();

        return [
            Stat::make('Members Committed', number_format($count))
                ->description('Total giving commitments')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Projected Annual', 'KES ' . number_format($projectedAnnual, 2))
                ->description('Expected yearly amount')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Avg. Annual / Member', 'KES ' . number_format($count > 0 ? $projectedAnnual / $count : 0, 2))
                ->description('Projected per pledger')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Fulfilled This Year', 'KES ' . number_format($fulfilledThisYear, 2))
                ->description('Installments recorded in ' . now()->year)
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Due Within 14 Days', number_format($dueSoon))
                ->description('Pledges needing follow-up')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color($dueSoon > 0 ? 'warning' : 'gray'),
        ];
    }
}
