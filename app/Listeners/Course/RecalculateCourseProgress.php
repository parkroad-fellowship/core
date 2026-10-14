<?php

namespace App\Listeners\Course;

use App\Events\Course\CourseCurriculumChanged;
use App\Jobs\Course\RecalculateProgressJob;

/**
 * Brings enrolled members' percentages in line with the new curriculum.
 */
class RecalculateCourseProgress
{
    public function handle(CourseCurriculumChanged $event): void
    {
        RecalculateProgressJob::dispatch($event->course);
    }
}
