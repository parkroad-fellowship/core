<?php

namespace Database\Seeders;

use App\Models\ClassGroup;
use Illuminate\Database\Seeder;

class ClassGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classGroups = [
            'Form 1',
            'Form 2',
            'Form 3',
            'Form 4',
            'Patrons',
            'CU Leaders',
            'Leaders',
        ];

        foreach ($classGroups as $classGroup) {
            ClassGroup::factory()
                ->create([
                    'name' => $classGroup,
                ]);
        }
    }
}
