<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Seeder;

class LessonModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Module::cursor() as $module) {
            // Attach lesson to a module
            $random = rand(3, 6);
            $module->lessonModules()->createMany(
                Lesson::inRandomOrder()->limit($random)->get()->map(function ($module) use ($random) {
                    return [
                        'lesson_id' => $module->id,
                        'order' => rand(1, $random),
                    ];
                })->toArray()
            );
        }
    }
}
