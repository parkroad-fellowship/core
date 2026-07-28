<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MissionPipelineWidget;
use App\Filament\Widgets\RecentAnnouncementsWidget;
use App\Filament\Widgets\RoleBasedStatsWidget;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\UpcomingEventsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'PRF Executive Dashboard';

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            RoleBasedStatsWidget::class,
            MissionPipelineWidget::class,
            RecentAnnouncementsWidget::class,
            UpcomingEventsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
