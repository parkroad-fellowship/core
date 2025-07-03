<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CourseEnrollmentChart;
use App\Filament\Widgets\ExpensesByCategoryChart;
use App\Filament\Widgets\MemberGrowthChart;
use App\Filament\Widgets\MissionsByTypeChart;
use App\Filament\Widgets\PrayerRequestsWidget;
use App\Filament\Widgets\RecentAnnouncementsWidget;
use App\Filament\Widgets\RoleBasedStatsWidget;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\UpcomingEventsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'PRF Dashboard';

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            RoleBasedStatsWidget::class,
            MemberGrowthChart::class,
            MissionsByTypeChart::class,
            ExpensesByCategoryChart::class,
            CourseEnrollmentChart::class,
            RecentAnnouncementsWidget::class,
            UpcomingEventsWidget::class,
            PrayerRequestsWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
