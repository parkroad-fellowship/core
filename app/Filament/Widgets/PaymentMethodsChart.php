<?php

namespace App\Filament\Widgets;

use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFLedgerChannel;
use App\Enums\PRFLedgerFlow;
use App\Models\LedgerEntry;
use Filament\Widgets\ChartWidget;

class PaymentMethodsChart extends ChartWidget
{
    protected ?string $heading = 'Income by Channel';

    protected static ?int $sort = 19;

    protected function getData(): array
    {
        $totals = LedgerEntry::query()
            ->whereYear('transacted_on', now()->year)
            ->where('flow', PRFLedgerFlow::RECEIPT)
            ->ofKind(PRFLedgerCategoryKind::INCOME)
            ->whereNotNull('channel')
            ->selectRaw('channel, sum(amount) as total')
            ->groupBy('channel')
            ->orderBy('channel')
            ->pluck('total', 'channel');

        $labels = [];
        $data = [];

        foreach ($totals as $channel => $total) {
            $labels[] = PRFLedgerChannel::tryFrom((int) $channel)?->getLabel() ?? 'Unspecified';
            $data[] = (int) $total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Amount (KES)',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(59, 130, 246)',
                        'rgb(234, 179, 8)',
                        'rgb(168, 85, 247)',
                        'rgb(20, 184, 166)',
                        'rgb(249, 115, 22)',
                        'rgb(239, 68, 68)',
                    ],
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
