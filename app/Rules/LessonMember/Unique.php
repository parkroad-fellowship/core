<?php

namespace App\Rules\LessonMember;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonMember;
use App\Models\Member;
use App\Models\Module;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Unique implements ValidationRule
{
    public function __construct(
        private string $lessonUlid,
        private string $moduleUlid,
        private string $courseUlid,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if the member is already subscribed to the lesson
        $exists = LessonMember::query()
            ->where([
                'member_id' => Member::query()
                    ->where('ulid', $value)
                    ->limit(1)
                    ->select('id'),
                'lesson_id' => Lesson::query()
                    ->where('ulid', $this->lessonUlid)
                    ->limit(1)
                    ->select('id'),
                'module_id' => Module::query()
                    ->where('ulid', $this->moduleUlid)
                    ->limit(1)
                    ->select('id'),
                'course_id' => Course::query()
                    ->where('ulid', $this->courseUlid)
                    ->limit(1)
                    ->select('id'),
            ])
            ->exists();

        if ($exists) {
            $fail('You are have already completed this lesson');
        }
    }
}
