<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DepartmentDistributionChart;
use App\Filament\Widgets\MemberRetentionWidget;
use App\Filament\Widgets\MembershipByTypeStats;
use App\Filament\Widgets\MembershipTrendsChart;
use Filament\Pages\Dashboard as BaseDashboard;

class MembershipDashboard extends BaseDashboard
{
    protected static ?string $title = 'Membership Analytics';

    protected static string $routePath = 'membership-analytics';

    protected static string|\UnitEnum|null $navigationGroup = 'Organising Secretary';

    protected static ?string $navigationLabel = 'Membership Analytics';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            MembershipByTypeStats::class,
            MembershipTrendsChart::class,
            DepartmentDistributionChart::class,
            MemberRetentionWidget::class,
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
