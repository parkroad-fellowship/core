<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MissionGroundSuggestionsWidget;
use App\Filament\Widgets\MissionPipelineWidget;
use App\Filament\Widgets\MissionRolesDistributionChart;
use App\Filament\Widgets\MissionsByTypeChart;
use App\Filament\Widgets\MissionSubscriptionTrendsChart;
use App\Filament\Widgets\MissionTypeBreakdownStats;
use App\Filament\Widgets\TopMissionersWidget;
use App\Filament\Widgets\UpcomingMissionsWidget;
use App\Models\Mission;
use Filament\Pages\Dashboard as BaseDashboard;

class MissionsDashboard extends BaseDashboard
{
    protected static ?string $title = 'Missions Analytics';

    protected static string $routePath = 'missions-analytics';

    protected static string|\UnitEnum|null $navigationGroup = 'Missions Secretary';

    protected static ?string $navigationLabel = 'Missions Analytics';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = 5;

    public function getWidgets(): array
    {
        return [
            UpcomingMissionsWidget::class,
            MissionPipelineWidget::class,
            MissionsByTypeChart::class,
            MissionSubscriptionTrendsChart::class,
            MissionRolesDistributionChart::class,
            MissionTypeBreakdownStats::class,
            TopMissionersWidget::class,
            MissionGroundSuggestionsWidget::class,
        ];
    }

    public static function canAccess(): bool
    {
        return userCan(Mission::permission('viewAny'));
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
