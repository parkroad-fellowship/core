<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Module;
use Illuminate\Database\Seeder;

class CourseModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Course::cursor() as $course) {
            // Attach modules to a course
            $random = rand(3, 6);
            $course->courseModules()->createMany(
                Module::inRandomOrder()->limit($random)->get()->map(function ($module) use ($random) {
                    return [
                        'module_id' => $module->id,
                        'order' => rand(1, $random),
                    ];
                })->toArray()
            );
        }
    }
}
