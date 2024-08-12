<?php

use App\Models\Member;
use App\Models\StudentEnquiry;
use Database\Factories\StudentEnquiryReplyFactory;
use Illuminate\Support\Facades\Artisan;

it('should return a list of replies to questions asked by students', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route(
        'api.student-enquiry-replies.index',
        [
            'include' => 'studentEnquiry',
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
                    'student_enquiry',
                ],
            ],
        ]);
});

it('should allow a user to record a reply to a question asked by a student', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $data = (new StudentEnquiryReplyFactory)->raw();

    // Act
    $response = actingAsUser()->post(
        route('api.student-enquiry-replies.store', [
            'include' => 'studentEnquiry',
        ]),
        [
            'content' => $data['content'],
            'student_enquiry_ulid' => StudentEnquiry::where('id', $data['student_enquiry_id'])->first()->ulid,
            'commentorable_type' => $data['commentorable_type']->value,
            'commentorable_ulid' => Member::where('id', $data['commentorable_id'])->first()->ulid,
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
                'student_enquiry',
            ],
        ]);
});
