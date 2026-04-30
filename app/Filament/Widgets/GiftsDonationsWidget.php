<?php

namespace App\Filament\Widgets;

use App\Models\Gift;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GiftsDonationsWidget extends BaseWidget
{
    protected static ?int $sort = 21;

    protected function getStats(): array
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        $totalGifts = Gift::where('is_active', true)->count();

        $membersWithGifts = Gift::query()
            ->where('is_active', true)
            ->withCount('members')
            ->get()
            ->sum('members_count');

        $totalPaymentsThisYear = Payment::query()
            ->whereYear('created_at', $currentYear)
            ->sum('amount') ?? 0;

        $monthlyPayments = Payment::query()
            ->whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->sum('amount') ?? 0;

        $avgMonthlyPayment = Payment::query()
            ->whereYear('created_at', $currentYear)
            ->avg('amount') ?? 0;

        return [
            Stat::make('Active Gifts', number_format($totalGifts))
                ->description('Available spiritual gifts')
                ->descriptionIcon('heroicon-m-gift')
                ->color('primary'),

            Stat::make('Members with Gifts', number_format($membersWithGifts))
                ->description('Gift assignments')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('YTD Contributions', 'KES '.number_format($totalPaymentsThisYear, 2))
                ->description('Total this year')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('This Month', 'KES '.number_format($monthlyPayments, 2))
                ->description('Contributions received')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }
}
