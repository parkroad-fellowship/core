<?php

namespace App\Jobs\LessonModule;

use App\Events\Course\CourseCurriculumChanged;
use App\Models\Lesson;
use App\Models\LessonModule;
use App\Models\Module;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Adds a lesson to the end of a module. A lesson that was removed earlier is restored rather
 * than added twice; one that is already in the module is refused.
 */
class CreateJob
{
    use Dispatchable;

    /**
     * @param  array<mixed>  $data  module_ulid, lesson_ulid
     */
    public function __construct(
        public array $data,
    ) {}

    public function handle(): LessonModule
    {
        return DB::transaction(function (): LessonModule {
            $module = Module::query()->where('ulid', $this->data['module_ulid'])->firstOrFail();
            $lesson = Lesson::query()->where('ulid', $this->data['lesson_ulid'])->firstOrFail();

            $link = LessonModule::query()
                ->withTrashed()
                ->where(['module_id' => $module->id, 'lesson_id' => $lesson->id])
                ->first();

            if ($link !== null && !$link->trashed()) {
                throw ValidationException::withMessages([
                    'lesson_ulid' => "“{$lesson->name}” is already in this module.",
                ]);
            }

            $last = LessonModule::query()->where('module_id', $module->id)->max('order');
            $order = (is_numeric($last) ? (int) $last : 0) + 1;

            if ($link !== null) {
                $link->restore();
                $link->update(['order' => $order]);
            } else {
                $link = LessonModule::query()->create([
                    'module_id' => $module->id,
                    'lesson_id' => $lesson->id,
                    'order' => $order,
                ]);
            }

            CourseCurriculumChanged::forModule($module);

            return $link;
        });
    }
}
