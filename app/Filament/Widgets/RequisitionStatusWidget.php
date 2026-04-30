<?php

namespace App\Filament\Widgets;

use App\Enums\PRFApprovalStatus;
use App\Models\Requisition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RequisitionStatusWidget extends BaseWidget
{
    protected static ?int $sort = 22;

    protected function getStats(): array
    {
        $currentYear = now()->year;

        $pendingCount = Requisition::query()
            ->where('approval_status', PRFApprovalStatus::PENDING->value)
            ->whereYear('created_at', $currentYear)
            ->count();

        $underReviewCount = Requisition::query()
            ->where('approval_status', PRFApprovalStatus::UNDER_REVIEW->value)
            ->whereYear('created_at', $currentYear)
            ->count();

        $approvedCount = Requisition::query()
            ->where('approval_status', PRFApprovalStatus::APPROVED->value)
            ->whereYear('created_at', $currentYear)
            ->count();

        $rejectedCount = Requisition::query()
            ->where('approval_status', PRFApprovalStatus::REJECTED->value)
            ->whereYear('created_at', $currentYear)
            ->count();

        $totalAmount = Requisition::query()
            ->where('approval_status', PRFApprovalStatus::APPROVED->value)
            ->whereYear('created_at', $currentYear)
            ->sum('total_amount') ?? 0;

        return [
            Stat::make('Pending', number_format($pendingCount))
                ->description('Awaiting review')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Under Review', number_format($underReviewCount))
                ->description('Being processed')
                ->descriptionIcon('heroicon-m-eye')
                ->color('primary'),

            Stat::make('Approved', number_format($approvedCount))
                ->description('This year')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Approved Amount', 'KES '.number_format($totalAmount, 2))
                ->description('Total approved')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
