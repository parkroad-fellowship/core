<?php

use App\Enums\PRFMissionStatus;
use App\Models\Mission;
use Database\Factories\MissionQuestionFactory;
use Illuminate\Support\Facades\Artisan;

it('should return a list of faqs asked by students with their answers', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route(
        'api.mission-faqs.index'
    ));

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'entity',
                    'ulid',
                    'question',
                    'answer'
                ],
            ],
        ]);
});

