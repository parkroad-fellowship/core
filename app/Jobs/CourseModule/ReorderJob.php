<?php

namespace App\Jobs\CourseModule;

use App\Events\Course\CourseCurriculumChanged;
use App\Models\Course;
use App\Models\CourseModule;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Numbers a course's modules 1..n in the given order.
 */
class ReorderJob
{
    use Dispatchable;

    /**
     * @param  list<string>  $ulids  the course's module links, first to last
     */
    public function __construct(
        public Course $course,
        public array $ulids,
    ) {}

    public function handle(): void
    {
        $positions = array_flip($this->ulids);

        CourseModule::query()
            ->where('course_id', $this->course->id)
            ->whereIn('ulid', $this->ulids)
            ->get()
            ->each(function (CourseModule $link) use ($positions): void {
                $order = $positions[$link->ulid] + 1;

                if ($link->order !== $order) {
                    $link->update(['order' => $order]);
                }
            });

        CourseCurriculumChanged::dispatch($this->course);
    }
}
