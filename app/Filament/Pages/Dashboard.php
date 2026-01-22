<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BudgetUtilizationChart;
use App\Filament\Widgets\CourseCompletionChart;
use App\Filament\Widgets\CourseEnrollmentChart;
use App\Filament\Widgets\DepartmentDistributionChart;
use App\Filament\Widgets\ExpensesByCategoryChart;
use App\Filament\Widgets\GiftsDonationsWidget;
use App\Filament\Widgets\IncomeVsExpenseChart;
use App\Filament\Widgets\LessonEngagementWidget;
use App\Filament\Widgets\MemberGrowthChart;
use App\Filament\Widgets\MemberRetentionWidget;
use App\Filament\Widgets\MembershipByTypeStats;
use App\Filament\Widgets\MembershipTrendsChart;
use App\Filament\Widgets\MissionGroundSuggestionsWidget;
use App\Filament\Widgets\MissionPipelineWidget;
use App\Filament\Widgets\MissionRolesDistributionChart;
use App\Filament\Widgets\MissionsByTypeChart;
use App\Filament\Widgets\MissionSubscriptionTrendsChart;
use App\Filament\Widgets\MissionTypeBreakdownStats;
use App\Filament\Widgets\PaymentMethodsChart;
use App\Filament\Widgets\PrayerRequestsStatusWidget;
use App\Filament\Widgets\PrayerRequestsWidget;
use App\Filament\Widgets\RecentAnnouncementsWidget;
use App\Filament\Widgets\RequisitionStatusWidget;
use App\Filament\Widgets\RoleBasedStatsWidget;
use App\Filament\Widgets\SoulsDecisionsChart;
use App\Filament\Widgets\SpiritualYearProgressWidget;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\StudentEnquiriesWidget;
use App\Filament\Widgets\TopMissionersWidget;
use App\Filament\Widgets\UpcomingEventsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'PRF Dashboard';

    public function getWidgets(): array
    {
        return [
            // Priority 1: Overview Stats (existing)
            StatsOverview::class,

            // Priority 2: Membership Health
            MembershipByTypeStats::class,
            MembershipTrendsChart::class,
            DepartmentDistributionChart::class,
            MemberRetentionWidget::class,

            // Priority 3: Mission Effectiveness
            MissionPipelineWidget::class,
            MemberGrowthChart::class,
            MissionsByTypeChart::class,
            MissionSubscriptionTrendsChart::class,
            MissionRolesDistributionChart::class,
            MissionTypeBreakdownStats::class,
            TopMissionersWidget::class,
            MissionGroundSuggestionsWidget::class,

            // Priority 4: Financial Stewardship
            IncomeVsExpenseChart::class,
            ExpensesByCategoryChart::class,
            GiftsDonationsWidget::class,
            RequisitionStatusWidget::class,
            BudgetUtilizationChart::class,
            PaymentMethodsChart::class,

            // Priority 5: Spiritual Impact
            SoulsDecisionsChart::class,
            PrayerRequestsStatusWidget::class,
            PrayerRequestsWidget::class,
            SpiritualYearProgressWidget::class,

            // Priority 6: Educational Progress
            CourseEnrollmentChart::class,
            CourseCompletionChart::class,
            LessonEngagementWidget::class,
            StudentEnquiriesWidget::class,

            // Priority 7: Operational (existing)
            RoleBasedStatsWidget::class,
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
