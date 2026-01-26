<?php

namespace App\Filament\Widgets;

use App\Enums\PRFEntryType;
use App\Models\AllocationEntry;
use App\Models\Course;
use App\Models\Member;
use App\Models\Mission;
use App\Models\Payment;
use App\Models\PrayerRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RoleBasedStatsWidget extends BaseWidget
{
    protected static ?int $sort = 10;

    protected function getStats(): array
    {
        $user = Auth::user();
        $stats = [];

        if (userCan('view members')) {
            $stats[] = Stat::make('Total Members', Member::count())
                ->description('Registered members')
                ->descriptionIcon('heroicon-m-users')
                ->color('success');

            $stats[] = Stat::make('New This Month', Member::whereMonth('created_at', now()->month)->count())
                ->description('New member registrations')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info');
        }

        if (userCan('view missions')) {
            $stats[] = Stat::make('Active Missions', Mission::where('status', 'active')->count())
                ->description('Currently running')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('primary');

            $stats[] = Stat::make('Total Courses', Course::count())
                ->description('E-learning courses')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('warning');
        }

        if (userCan('view expenses')) {
            $monthlyIncome = Payment::whereMonth('created_at', now()->month)->sum('amount');
            $monthlyExpenses = AllocationEntry::whereMonth('created_at', now()->month)->where('entry_type', PRFEntryType::DEBIT)->sum('amount');

            $stats[] = Stat::make('Monthly Income', 'KES '.number_format($monthlyIncome, 2))
                ->description('This month\'s income')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success');

            $stats[] = Stat::make('Monthly Expenses', 'KES '.number_format($monthlyExpenses, 2))
                ->description('This month\'s expenses')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger');

            $stats[] = Stat::make('Net Balance', 'KES '.number_format($monthlyIncome - $monthlyExpenses, 2))
                ->description('Income - Expenses')
                ->descriptionIcon('heroicon-m-scale')
                ->color($monthlyIncome > $monthlyExpenses ? 'success' : 'danger');
        }

        if (userCan('view prayer requests')) {
            $stats[] = Stat::make('Open Prayer Requests', PrayerRequest::where('status', 'open')->count())
                ->description('Needs attention')
                ->descriptionIcon('heroicon-m-hand-raised')
                ->color('warning');

            $stats[] = Stat::make('Answered Prayers', PrayerRequest::where('status', 'answered')->count())
                ->description('Praise reports')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success');
        }

        return $stats;
    }
}
