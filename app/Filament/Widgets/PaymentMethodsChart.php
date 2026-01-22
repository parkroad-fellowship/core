<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PaymentMethodsChart extends ChartWidget
{
    protected ?string $heading = 'Payment Methods Distribution';

    protected static ?int $sort = 19;

    protected function getData(): array
    {
        $currentYear = now()->year;

        $payments = Payment::query()
            ->select('payment_type_id', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as count'))
            ->whereYear('created_at', $currentYear)
            ->whereNotNull('payment_type_id')
            ->groupBy('payment_type_id')
            ->with('paymentType')
            ->get();

        $labels = [];
        $data = [];

        foreach ($payments as $payment) {
            $labels[] = $payment->paymentType->name ?? 'Unknown';
            $data[] = $payment->total_amount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Amount (KES)',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',   // Green
                        'rgb(59, 130, 246)',  // Blue
                        'rgb(234, 179, 8)',   // Yellow
                        'rgb(168, 85, 247)',  // Purple
                        'rgb(20, 184, 166)',  // Teal
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
