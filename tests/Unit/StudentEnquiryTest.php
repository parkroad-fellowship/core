<?php

use App\Models\Student;
use Database\Factories\StudentEnquiryFactory;
use Illuminate\Support\Facades\Artisan;

it('should return a list of questions asked by students', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route(
        'api.student-enquiries.index',
        [
            'include' => 'missionFaq,student',
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
                    'content',
                    'mission_faq',
                    'student',
                ],
            ],
        ]);
});

it('should allow a user to record a question asked by a student', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $data = (new StudentEnquiryFactory)->raw();

    // Act
    $response = actingAsUser()->post(
        route('api.student-enquiries.store', [
            'include' => 'missionFaq,student',
        ]),
        [
            'content' => $data['content'],
            'student_ulid' => Student::where('id', $data['student_id'])->first()->ulid,
        ],
    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'content',
                'mission_faq',
                'student',
            ],
        ]);
});
