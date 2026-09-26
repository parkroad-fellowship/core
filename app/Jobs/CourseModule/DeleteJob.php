<?php

namespace App\Jobs\CourseModule;

use App\Models\CourseModule;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

/**
 * Takes a module out of a course. The module and its lessons stay in the library.
 */
class DeleteJob
{
    use Dispatchable;

    public function __construct(
        public CourseModule $courseModule,
        public User $actor,
    ) {}

    public function handle(): void
    {
        DB::transaction(function (): void {
            $course = $this->courseModule->course;
            $this->courseModule->delete();

            ReorderJob::dispatchSync(
                $course,
                array_values(
                    CourseModule::query()
                        ->where('course_id', $course->id)
                        ->orderBy('order')
                        ->get()
                        ->map(fn(CourseModule $link): string => $link->ulid)
                        ->all(),
                ),
            );
        });
    }
}
