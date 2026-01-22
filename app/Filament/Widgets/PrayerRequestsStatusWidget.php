<?php

namespace App\Filament\Widgets;

use App\Models\PrayerRequest;
use App\Models\PrayerResponse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PrayerRequestsStatusWidget extends BaseWidget
{
    protected static ?int $sort = 8;

    protected function getStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $totalRequests = PrayerRequest::query()
            ->whereYear('created_at', $currentYear)
            ->count();

        $monthlyRequests = PrayerRequest::query()
            ->whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->count();

        $totalResponses = PrayerResponse::query()
            ->whereYear('created_at', $currentYear)
            ->count();

        $monthlyResponses = PrayerResponse::query()
            ->whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->count();

        $respondedRequests = PrayerRequest::query()
            ->whereHas('member.prayerResponses')
            ->whereYear('created_at', $currentYear)
            ->count();

        $responseRate = $totalRequests > 0
            ? round(($respondedRequests / $totalRequests) * 100)
            : 0;

        return [
            Stat::make('Prayer Requests (YTD)', number_format($totalRequests))
                ->description('Total prayer requests this year')
                ->descriptionIcon('heroicon-m-hand-raised')
                ->color('primary'),

            Stat::make('This Month', number_format($monthlyRequests))
                ->description('New prayer requests')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('Prayer Responses', number_format($totalResponses))
                ->description('Members praying')
                ->descriptionIcon('heroicon-m-heart')
                ->color('success'),

            Stat::make('Response Rate', $responseRate.'%')
                ->description('Requests with responses')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($responseRate >= 50 ? 'success' : 'warning'),
        ];
    }
}
