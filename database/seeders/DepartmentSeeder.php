<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'Admin',
            'Children',
            'Communication',
            'Counseling',
            'Creative Arts',
            'Discipleship',
            'Evangelism',
            'Finance',
            'Hospitality',
            'HR',
            'IT',
            'Media',
            'Missions',
            'Music',
            'Prayer',
            'Protocol',
            'Sound',
            'Teaching',
            'Ushering',
            'Youth',
        ];

        foreach ($departments as $department) {
            Department::factory()
                ->create([
                    'name' => $department,
                ]);
        }
    }
}
