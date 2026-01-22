<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use Carbon\Carbon;
use Exception;
use Filament\Widgets\ChartWidget;

class MemberGrowthChart extends ChartWidget
{
    protected ?string $heading = 'Member Growth Over Time';

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        try {
            // Get all members from the last 12 months
            $members = Member::where('created_at', '>=', now()->subMonths(12))
                ->orderBy('created_at')
                ->get();

            // Group by month manually using Carbon
            $membersByMonth = [];
            foreach ($members as $member) {
                $monthKey = Carbon::parse($member->created_at)->format('Y-m');
                if (! isset($membersByMonth[$monthKey])) {
                    $membersByMonth[$monthKey] = 0;
                }
                $membersByMonth[$monthKey]++;
            }

            $labels = [];
            $data = [];

            foreach ($membersByMonth as $month => $count) {
                $labels[] = Carbon::parse($month.'-01')->format('M Y');
                $data[] = $count;
            }

            return [
                'datasets' => [
                    [
                        'label' => 'New Members',
                        'data' => $data,
                        'borderColor' => 'rgb(75, 192, 192)',
                        'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                        'fill' => true,
                    ],
                ],
                'labels' => $labels,
            ];
        } catch (Exception $e) {
            // Return empty chart data if there's an error
            return [
                'datasets' => [
                    [
                        'label' => 'New Members',
                        'data' => [],
                        'borderColor' => 'rgb(75, 192, 192)',
                        'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                        'fill' => true,
                    ],
                ],
                'labels' => [],
            ];
        }
    }

    protected function getType(): string
    {
        return 'line';
    }
}
