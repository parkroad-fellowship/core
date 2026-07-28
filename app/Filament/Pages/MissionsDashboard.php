<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MemberGrowthChart;
use App\Filament\Widgets\MissionGroundSuggestionsWidget;
use App\Filament\Widgets\MissionPipelineWidget;
use App\Filament\Widgets\MissionRolesDistributionChart;
use App\Filament\Widgets\MissionsByTypeChart;
use App\Filament\Widgets\MissionSubscriptionTrendsChart;
use App\Filament\Widgets\MissionTypeBreakdownStats;
use App\Filament\Widgets\TopMissionersWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class MissionsDashboard extends BaseDashboard
{
    protected static ?string $title = 'Missions Analytics';

    protected static string $routePath = 'missions-analytics';

    protected static string|\UnitEnum|null $navigationGroup = 'Missions Secretary';

    protected static ?string $navigationLabel = 'Missions Analytics';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            MissionPipelineWidget::class,
            MissionsByTypeChart::class,
            MissionSubscriptionTrendsChart::class,
            MissionRolesDistributionChart::class,
            MissionTypeBreakdownStats::class,
            MemberGrowthChart::class,
            TopMissionersWidget::class,
            MissionGroundSuggestionsWidget::class,
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
