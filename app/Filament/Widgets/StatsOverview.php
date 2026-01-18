<?php

namespace App\Filament\Widgets;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFEntryType;
use App\Enums\PRFMissionStatus;
use App\Models\AllocationEntry;
use App\Models\Course;
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
        $currentYear = now()->year;

        $totalMembers = Member::query()
            ->where('is_desk_email', false)
            ->where([
                'approved' => true,
            ])
            ->count();
        $activeMissions = Mission::whereIn('status', [
            PRFMissionStatus::APPROVED,
            PRFMissionStatus::FULLY_SUBSCRIBED,
        ])->whereYear('start_date', $currentYear)->count();
        $totalSouls = Soul::whereYear('created_at', $currentYear)->count();
        $activeCourses = Course::where('is_active', PRFActiveStatus::ACTIVE)->count();
        $upcomingEvents = PRFEvent::where('start_date', '>=', now())->count();
        $monthlyExpenses = AllocationEntry::whereMonth('created_at', now()->month)
            ->where('entry_type', PRFEntryType::DEBIT->value)
            ->sum('amount') ?? 0;
        $yearToDateExpenses = AllocationEntry::whereYear('created_at', $currentYear)
            ->where('entry_type', PRFEntryType::DEBIT->value)
            ->sum('amount') ?? 0;
        $missionsBooked = Mission::whereIn('status', [
            PRFMissionStatus::APPROVED,
            PRFMissionStatus::FULLY_SUBSCRIBED,
            PRFMissionStatus::SERVICED,
        ])->whereYear('start_date', $currentYear)
            ->count();
        $missionsServiced = Mission::whereIn('status', [
            PRFMissionStatus::SERVICED,
        ])->whereYear('start_date', $currentYear)
            ->count();

        $activeMissioners = Mission::query()
            ->whereYear('missions.start_date', $currentYear)
            ->join('mission_subscriptions', 'missions.id', '=', 'mission_subscriptions.mission_id')
            ->join('members', 'members.id', '=', 'mission_subscriptions.member_id')
            ->whereNull('mission_subscriptions.deleted_at')
            ->where('mission_subscriptions.status', PRFMissionStatus::APPROVED)
            ->whereNull('missions.deleted_at')
            ->where('missions.start_date', '<', now())
            ->distinct()
            ->count('members.id');

        return [
            Stat::make('Total Members', number_format($totalMembers))
                ->description('Registered app members')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Active Missioners', number_format($activeMissioners))
                ->description('Members currently on missions')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Missions Booked', number_format($missionsBooked))
                ->description('Total missions booked')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('primary'),

            Stat::make('Active Missions', number_format($activeMissions))
                ->description('Currently running missions')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('primary'),

            Stat::make('Missions Serviced', number_format($missionsServiced))
                ->description('Total missions serviced')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('success'),

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

            Stat::make('Year-To-Date Expenses', 'KES '.number_format($yearToDateExpenses, 2))
                ->description('Year-to-date expenses')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
        ];
    }
}
