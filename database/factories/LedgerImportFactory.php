<?php

namespace Database\Factories;

use App\Enums\PRFProcessingStatus;
use App\Models\LedgerImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerImport>
 */
class LedgerImportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'original_name' => 'Financials.xlsx',
            'year' => (int) now()->format('Y'),
            'status' => PRFProcessingStatus::PENDING,
        ];
    }
}
