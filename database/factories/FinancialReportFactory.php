<?php

namespace Database\Factories;

use App\Enums\PRFFinancialReportType;
use App\Enums\PRFProcessingStatus;
use App\Models\FinancialReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialReport>
 */
class FinancialReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => PRFFinancialReportType::CASHBOOK,
            'period_start' => now()->startOfYear(),
            'period_end' => now()->endOfMonth(),
            'status' => PRFProcessingStatus::PENDING,
        ];
    }
}
