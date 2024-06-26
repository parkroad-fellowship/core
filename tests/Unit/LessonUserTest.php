<?php

use App\Enums\PRFCompletionStatus;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\LessonModule;
use App\Models\Member;
use App\Models\Module;
use Illuminate\Support\Facades\Artisan;

it('should allow a user to record a they have finished a lesson', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $courseModule = CourseModule::first();
    $lessonModule = LessonModule::query()
        ->where('module_id', $courseModule->module_id)
        ->first();

    // Act
    $response = actingAsUser()->post(
        route('api.lesson-members.store', []),
        [
            'course_ulid' => Course::query()->where('id', $courseModule->course_id)->first()->ulid,
            'module_ulid' => Module::query()->where('id', $lessonModule->module_id)->first()->ulid,
            'lesson_ulid' => Lesson::query()->where('id', $lessonModule->lesson_id)->first()->ulid,
            'member_ulid' => Member::first()->ulid,
            'completion_status' => PRFCompletionStatus::COMPLETE->value,
        ],
    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',

                'ulid',
                'completion_status',
                'completed_at',

                'created_at',
                'updated_at',
            ],
        ]);
})->skip();
