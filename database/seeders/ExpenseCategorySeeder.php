<?php

namespace Database\Seeders;

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
                'name' => 'Snacks',
                'description' => 'Food expenses',
            ],
        ];

        foreach ($expenseCategories as $expenseCategory) {
            \App\Models\ExpenseCategory::updateOrCreate([
                'name' => $expenseCategory['name'],
            ], $expenseCategory);
        }
    }
}
