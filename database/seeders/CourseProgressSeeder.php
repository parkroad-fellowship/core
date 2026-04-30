<?php

namespace Database\Seeders;

use App\Enums\PRFCompletionStatus;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseModule;
use App\Models\LessonMember;
use App\Models\LessonModule;
use App\Models\Member;
use App\Models\MemberModule;
use Illuminate\Database\Seeder;

class CourseProgressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Member::cursor() as $member) {
            foreach (Course::cursor() as $course) {
                foreach (CourseModule::query()
                    ->where('course_id', $course->id)
                    ->orderBy('order', 'asc')
                    ->cursor() as $courseModule) {

                    foreach (LessonModule::query()
                        ->where('module_id', $courseModule->module_id)
                        ->orderBy('order', 'asc')
                        ->cursor() as $lessonModule) {

                        LessonMember::create([
                            'course_id' => $course->id,
                            'module_id' => $courseModule->module_id,
                            'lesson_id' => $lessonModule->lesson_id,
                            'member_id' => $member->id,
                            'completion_status' => PRFCompletionStatus::getElements()[rand(0, 1)],

                        ]);
                    }

                    try {
                        MemberModule::create([
                            'course_id' => $course->id,
                            'module_id' => $courseModule->module_id,
                            'member_id' => $member->id,
                            'completion_status' => PRFCompletionStatus::getElements()[rand(0, 1)],
                        ]);
                    } catch (\Exception $e) {
                    }
                }

                try {
                    CourseMember::create([
                        'course_id' => $course->id,
                        'member_id' => $member->id,
                        'completion_status' => PRFCompletionStatus::getElements()[rand(0, 1)],
                    ]);
                } catch (\Exception $e) {
                }
            }
        }
    }
}
