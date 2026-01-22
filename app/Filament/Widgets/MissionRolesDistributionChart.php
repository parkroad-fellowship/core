<?php

namespace App\Filament\Widgets;

use App\Enums\PRFMissionRole;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\MissionSubscription;
use Filament\Widgets\ChartWidget;

class MissionRolesDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Mission Roles Distribution';

    protected static ?int $sort = 17;

    protected function getData(): array
    {
        $currentYear = now()->year;

        $labels = [];
        $data = [];
        $colors = [
            'rgb(156, 163, 175)', // Member - gray
            'rgb(239, 68, 68)',   // Leader - red
            'rgb(234, 179, 8)',   // Assistant Leader - yellow
            'rgb(34, 197, 94)',   // Discipleship Trainer - green
            'rgb(59, 130, 246)',  // Music Instruments - blue
            'rgb(168, 85, 247)',  // Transportation - purple
        ];

        $roleIndex = 0;
        foreach (PRFMissionRole::cases() as $role) {
            $count = MissionSubscription::query()
                ->where('mission_role', $role->value)
                ->where('status', PRFMissionSubscriptionStatus::APPROVED->value)
                ->whereHas('mission', function ($query) use ($currentYear) {
                    $query->whereYear('start_date', $currentYear);
                })
                ->count();

            if ($count > 0) {
                $labels[] = $role->getLabel();
                $data[] = $count;
            }
            $roleIndex++;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Subscriptions',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
