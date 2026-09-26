<?php

namespace App\Filament\Widgets;

use App\Enums\PRFMissionRole;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Filament\Support\PRFPalette;
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
            PRFPalette::NEUTRAL, // Member
            PRFPalette::DANGER, // Leader
            PRFPalette::WARNING, // Assistant Leader
            PRFPalette::SUCCESS, // Discipleship Trainer
            PRFPalette::INFO, // Music Instruments
            PRFPalette::NAVY, // Transportation
        ];

        $roleIndex = 0;
        foreach (PRFMissionRole::cases() as $role) {
            $count = MissionSubscription::query()
                ->where('mission_role', $role)
                ->where('status', PRFMissionSubscriptionStatus::APPROVED)
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
