<?php

namespace Database\Seeders;

use App\Events\CoolBeans;
use App\Models\Course;
use Illuminate\Database\Seeder;

class TriggerEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $course = Course::with('courseModules')->first();

        $resource = new \App\Http\Resources\Course\Resource($course);

        CoolBeans::dispatch($resource);
    }
}
