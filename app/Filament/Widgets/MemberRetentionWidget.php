<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MemberRetentionWidget extends BaseWidget
{
    protected static ?int $sort = 20;

    protected function getStats(): array
    {
        $totalApproved = Member::query()
            ->where('approved', true)
            ->where('is_desk_email', false)
            ->count();

        $activeMembers = Member::query()
            ->where('approved', true)
            ->where('is_desk_email', false)
            ->where('updated_at', '>=', now()->subMonths(6))
            ->count();

        $newThisMonth = Member::query()
            ->where('approved', true)
            ->where('is_desk_email', false)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $newLastMonth = Member::query()
            ->where('approved', true)
            ->where('is_desk_email', false)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $growthRate = $newLastMonth > 0
            ? round((($newThisMonth - $newLastMonth) / $newLastMonth) * 100)
            : ($newThisMonth > 0 ? 100 : 0);

        $retentionRate = $totalApproved > 0
            ? round(($activeMembers / $totalApproved) * 100)
            : 0;

        return [
            Stat::make('Active Members', number_format($activeMembers))
                ->description('Active in last 6 months')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Retention Rate', $retentionRate.'%')
                ->description('Members still active')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($retentionRate >= 70 ? 'success' : ($retentionRate >= 50 ? 'warning' : 'danger')),

            Stat::make('New This Month', number_format($newThisMonth))
                ->description('New registrations')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info'),

            Stat::make('Growth Rate', ($growthRate >= 0 ? '+' : '').$growthRate.'%')
                ->description('vs last month')
                ->descriptionIcon($growthRate >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($growthRate >= 0 ? 'success' : 'danger'),
        ];
    }
}
