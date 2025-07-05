<?php

namespace App\Filament\Widgets;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFMissionStatus;
use App\Models\Course;
use App\Models\Expense;
use App\Models\Member;
use App\Models\Mission;
use App\Models\PRFEvent;
use App\Models\Soul;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalMembers = Member::query()
            ->whereNotIn('email', config('prf.app.excluded_emails'))
            ->where([
                'approved' => true,
            ])
            ->count();
        $activeMissions = Mission::whereIn('status', [
            PRFMissionStatus::APPROVED,
            PRFMissionStatus::FULLY_SUBSCRIBED,
        ])->count();
        $totalSouls = Soul::count();
        $activeCourses = Course::where('is_active', PRFActiveStatus::ACTIVE)->count();
        $upcomingEvents = PRFEvent::where('start_date', '>=', now())->count();
        $monthlyExpenses = Expense::whereMonth('created_at', now()->month)->sum('line_total') ?? 0;

        return [
            Stat::make('Total Members', number_format($totalMembers))
                ->description('Registered app members')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Active Missions', number_format($activeMissions))
                ->description('Currently running missions')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('primary'),

            Stat::make('Souls Reached', number_format($totalSouls))
                ->description('Decisions made for Christ')
                ->descriptionIcon('heroicon-m-heart')
                ->color('warning'),

            Stat::make('Active Courses', number_format($activeCourses))
                ->description('E-learning courses')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Upcoming Events', number_format($upcomingEvents))
                ->description('Scheduled events')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('gray'),

            Stat::make('Monthly Expenses', 'KES '.number_format($monthlyExpenses, 2))
                ->description('This month\'s expenses')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
        ];
    }
}
