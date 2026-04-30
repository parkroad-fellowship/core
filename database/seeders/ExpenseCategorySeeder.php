<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expenseCategories = [
            [
                'name' => 'Fare',
                'description' => 'Transportation expenses',
            ],
            [
                'name' => 'Fuel',
                'description' => 'Where a person spends money on fuel for their vehicle',
            ],
            [
                'name' => 'Accommodation',
                'description' => 'Accommodation expenses',
            ],
            [
                'name' => 'Snacks',
                'description' => 'Food expenses',
            ],
            [
                'name' => 'Airtime & Data',
                'description' => 'Communication expenses',
            ],
        ];

        foreach ($expenseCategories as $expenseCategory) {
            ExpenseCategory::updateOrCreate([
                'name' => $expenseCategory['name'],
            ], $expenseCategory);
        }
    }
}
