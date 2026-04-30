<?php

namespace App\Filament\Widgets;

use App\Enums\PRFMembershipType;
use App\Models\Membership;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MembershipByTypeStats extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $currentYear = now()->year;

        $friendsCount = Membership::query()
            ->where('type', PRFMembershipType::FRIEND->value)
            ->where('approved', true)
            ->whereYear('created_at', $currentYear)
            ->count();

        $yearlyMembersCount = Membership::query()
            ->where('type', PRFMembershipType::YEARLY_MEMBER->value)
            ->where('approved', true)
            ->whereYear('created_at', $currentYear)
            ->count();

        $lifetimeMembersCount = Membership::query()
            ->where('type', PRFMembershipType::LIFETIME_MEMBER->value)
            ->where('approved', true)
            ->count();

        $totalMemberships = Membership::query()
            ->where('approved', true)
            ->whereYear('created_at', $currentYear)
            ->count();

        return [
            Stat::make('Friends', number_format($friendsCount))
                ->description('Non-paying supporters')
                ->descriptionIcon('heroicon-m-heart')
                ->color('gray'),

            Stat::make('Yearly Members', number_format($yearlyMembersCount))
                ->description('Annual membership')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),

            Stat::make('Lifetime Members', number_format($lifetimeMembersCount))
                ->description('Permanent membership')
                ->descriptionIcon('heroicon-m-star')
                ->color('success'),

            Stat::make('Total Memberships', number_format($totalMemberships))
                ->description('This year')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }
}
