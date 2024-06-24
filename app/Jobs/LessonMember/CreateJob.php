<?php

namespace App\Jobs\LessonMember;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonMember;
use App\Models\Member;
use App\Models\Module;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): LessonMember
    {
        $data = $this->data;

        $course = Course::where('ulid', $data['course_ulid'])->firstOrFail();
        $module = Module::where('ulid', $data['module_ulid'])->firstOrFail();
        $lesson = Lesson::where('ulid', $data['lesson_ulid'])->firstOrFail();
        $member = Member::where('ulid', $data['member_ulid'])->firstOrFail();

        return LessonMember::create(
            [
                'course_id' => $course->id,
                'module_id' => $module->id,
                'lesson_id' => $lesson->id,
                'member_id' => $member->id,
                'completion_status' => $data['completion_status'],
                'completed_at' => Carbon::now(),
            ],
        );
    }
}
