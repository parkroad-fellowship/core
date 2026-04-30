<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            'Admin',
            'Finance',
            'HR',
            'Media',
            'IT',
        ];

        foreach ($departments as $department) {
            Department::factory()
                ->create([
                    'name' => $department,
                ]);
        }
    }
}
