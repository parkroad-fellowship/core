<?php

namespace App\Filament\Widgets;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\MissionSubscription;
use Filament\Widgets\ChartWidget;

class MissionSubscriptionTrendsChart extends ChartWidget
{
    protected ?string $heading = 'Mission Subscription Trends';

    protected static ?int $sort = 13;

    protected function getData(): array
    {
        $months = collect();
        $subscriptionsData = collect();
        $approvedData = collect();

        // Get data for the last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push($date->format('M Y'));

            $subscriptionsData->push(
                MissionSubscription::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count()
            );

            $approvedData->push(
                MissionSubscription::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('status', PRFMissionSubscriptionStatus::APPROVED->value)
                    ->count()
            );
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Subscriptions',
                    'data' => $subscriptionsData->toArray(),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'fill' => true,
                ],
                [
                    'label' => 'Approved',
                    'data' => $approvedData->toArray(),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'fill' => true,
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
