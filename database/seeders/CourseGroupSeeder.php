<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Group;
use Illuminate\Database\Seeder;

class CourseGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Group::cursor() as $group) {
            // Attach courses to a group
            $random = rand(3, 6);
            $group->courseGroups()->createMany(
                Course::inRandomOrder()->limit($random)->get()->map(function ($course) use ($random) {
                    return [
                        'course_id' => $course->id,
                        'start_date' => now()->addDays($random),
                    ];
                })->toArray()
            );
        }
    }
}
