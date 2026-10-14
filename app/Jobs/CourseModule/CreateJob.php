<?php

namespace App\Jobs\CourseModule;

use App\Events\Course\CourseCurriculumChanged;
use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Module;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Adds a module to the end of a course. A module that was removed earlier is restored rather
 * than added twice; one that is already in the course is refused.
 */
class CreateJob
{
    use Dispatchable;
    use ResolvesULIDs;

    /**
     * @param  array<mixed>  $data  course_ulid, module_ulid
     */
    public function __construct(
        public array $data,
    ) {}

    public function handle(): CourseModule
    {
        return DB::transaction(function (): CourseModule {
            $course = Course::query()->where('ulid', $this->data['course_ulid'])->firstOrFail();
            $module = Module::query()->where('ulid', $this->data['module_ulid'])->firstOrFail();

            $link = CourseModule::query()
                ->withTrashed()
                ->where(['course_id' => $course->id, 'module_id' => $module->id])
                ->first();

            if ($link !== null && !$link->trashed()) {
                throw ValidationException::withMessages([
                    'module_ulid' => "“{$module->name}” is already in this course.",
                ]);
            }

            $last = CourseModule::query()->where('course_id', $course->id)->max('order');
            $order = (is_numeric($last) ? (int) $last : 0) + 1;

            if ($link !== null) {
                $link->restore();
                $link->update(['order' => $order]);
            } else {
                $link = CourseModule::query()->create([
                    'course_id' => $course->id,
                    'module_id' => $module->id,
                    'order' => $order,
                ]);
            }

            CourseCurriculumChanged::dispatch($course);

            return $link;
        });
    }
}
