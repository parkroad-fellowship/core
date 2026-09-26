<?php

namespace App\Events\Course;

use App\Models\Course;
use App\Models\Module;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Modules or lessons were added to, removed from or reordered in a course.
 */
class CourseCurriculumChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Course $course,
    ) {}

    /**
     * A module's lessons changed: every course that uses the module changed with it.
     */
    public static function forModule(Module $module): void
    {
        Course::query()
            ->whereHas('courseModules', fn($query) => $query->where('module_id', $module->id))
            ->get()
            ->each(fn(Course $course) => self::dispatch($course));
    }
}
