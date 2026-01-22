<?php

namespace App\Filament\Widgets;

use App\Enums\PRFMembershipType;
use App\Models\Membership;
use Filament\Widgets\ChartWidget;

class MembershipTrendsChart extends ChartWidget
{
    protected ?string $heading = 'Membership Trends';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $months = collect();
        $friendsData = collect();
        $yearlyData = collect();
        $lifetimeData = collect();

        // Get data for the last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push($date->format('M Y'));

            $friendsData->push(
                Membership::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('type', PRFMembershipType::FRIEND->value)
                    ->where('approved', true)
                    ->count()
            );

            $yearlyData->push(
                Membership::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('type', PRFMembershipType::YEARLY_MEMBER->value)
                    ->where('approved', true)
                    ->count()
            );

            $lifetimeData->push(
                Membership::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('type', PRFMembershipType::LIFETIME_MEMBER->value)
                    ->where('approved', true)
                    ->count()
            );
        }

        return [
            'datasets' => [
                [
                    'label' => 'Friends',
                    'data' => $friendsData->toArray(),
                    'borderColor' => 'rgb(156, 163, 175)',
                    'backgroundColor' => 'rgba(156, 163, 175, 0.2)',
                    'fill' => false,
                ],
                [
                    'label' => 'Yearly Members',
                    'data' => $yearlyData->toArray(),
                    'borderColor' => 'rgb(234, 179, 8)',
                    'backgroundColor' => 'rgba(234, 179, 8, 0.2)',
                    'fill' => false,
                ],
                [
                    'label' => 'Lifetime Members',
                    'data' => $lifetimeData->toArray(),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'fill' => false,
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
