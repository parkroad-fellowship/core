<?php

use App\Enums\PRFCompletionStatus;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\LessonModule;
use App\Models\Member;
use App\Models\Module;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

it('should return a list of announcements by the OS', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route(
        'api.announcement-groups.index',
        [
            'include' => 'announcement',
        ]
    ));

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'entity',
                    'ulid',
                    'created_at',
                    'updated_at',
                    'announcement' => [
                        'entity',
                        'ulid',
                        'title',
                        'content',
                        'created_at',
                        'updated_at',
                    ]
                ],
            ],
        ]);
});
