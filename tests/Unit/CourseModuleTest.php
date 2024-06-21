<?php

use Illuminate\Support\Facades\Artisan;

it('should return a list of course modules', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route(
        'api.course-modules.index',
        [
            'include' => 'course.thumbnail,course.courseMember,module.thumbnail,module.memberModule,module.lessonModules.lesson,module.lessonModules.lessonMember',
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
                    'order',
                    'created_at',
                    'updated_at',
                    'course' => [
                        'entity',
                        'ulid',
                        'name',
                        'slug',
                        'description',
                        'is_active',
                        'thumbnail',
                        'course_member',
                    ],
                    'module' => [
                        'entity',
                        'ulid',
                        'name',
                        'slug',
                        'description',
                        'is_active',
                        'thumbnail',
                        'member_module',
                        'lesson_modules' => [
                            '*' => [
                                'entity',
                                'ulid',
                                'order',
                                'lesson_member',
                                'lesson' => [
                                    'entity',
                                    'ulid',
                                    'name',
                                    'slug',
                                    'description',
                                    'is_active',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
});
